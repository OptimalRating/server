<?php

namespace App\Http\Controllers\Api\Auth;

use App\Country;
use App\Friends;
use App\Role;
use App\Service\CustomJsonResponse;
use App\Service\IpService;
use App\Service\SocialAuthentication\FacebookAuthentication;
use App\Service\SocialAuthentication\GoogleAuthentication;
use App\Service\UserPrivacyService;
use App\User;
use App\Validator\LoginValidator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log; // Import the Log facade
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Client;
use Laravel\Socialite\Facades\Socialite;
use Google_Client;


class LoginController extends Controller
{
    private $client;
    /**
     * @var CustomJsonResponse
     */
    private $jsonResponse;


    public function __construct(CustomJsonResponse $jsonResponse)
    {
        $this->client  = Client::find(2);
        $this->jsonResponse = $jsonResponse;
    }

    //updated by Muskan
//     public function login(Request $request, CustomJsonResponse $customJsonResponse)
//    {
//     $validator = new LoginValidator();

//     if ($response = $validator->validate()) {
//         return $response;
//     }

//     $email = $request->json('email');

//     //handle login request
//     $params = [
//         'grant_type' => 'password',
//         'client_id' => $this->client->id,
//         'client_secret' => $this->client->secret,
//         'username' => $request->json('email') ?: $request->json('username'),
//         'password' => $request->json('password'),
//         'scope' => '',
//     ];

//     $request->request->add($params);

//     $proxy = Request::create('oauth/token', 'POST');

//     $response = Route::dispatch($proxy);

//     if ($response->getStatusCode() == 200) {
//     $user = User::findForLogin($params['username']);

//     if(!$user){
//     // Retrieve the user and check if they are not deleted
//     $user = User::where(function($query) use ($params) {
//                 $query->where('email', $params['username']) 
//                       ->orWhere('username', $params['username']);
//             })->whereNull('deleted_at')->first();
//             // Log::info('USER====>', ['user' => $user]);
//     }
//     if (!$user) {
//     return $customJsonResponse->setData(401, 'msg.error.your_account_deleted')->getResponse();
//     }
//         $token = json_decode($response->getContent(), null, 512, JSON_THROW_ON_ERROR);
//         return $this->createResponse($user, $token);
//     }

//     return $customJsonResponse->setData(401, 'msg.error.invalid_credentials', json_decode($response->getContent(), null, 512, JSON_THROW_ON_ERROR))->getResponse();
// }


public function login(Request $request, CustomJsonResponse $customJsonResponse)
{
    $validator = new LoginValidator();

    if ($response = $validator->validate()) {
        return $response;
    }

    $username = $request->json('email') ?? $request->json('username');
    $password = $request->json('password');

    try {
        // Send the token request just like in register()
       $response = Http::asForm()->post('https://server.optimalrating.com/oauth/token', [
       'grant_type' => 'password',
       'client_id' => '9',
       'client_secret' => '8OdVLaNYyA6gFBlmYbyOSbzYmuE8vjWxhIXXPua1',
       'username' => $username,
       'password' => $password,
       'scope' => '',
        ]);


        if ($response->status() === 200) {
            $token = $response->json();

            $user = User::findForLogin($username);

            if (!$user) {
                $user = User::where(function ($query) use ($username) {
                    $query->where('email', $username)
                          ->orWhere('username', $username);
                })->whereNull('deleted_at')->first();
            }

            if (!$user) {
                return $customJsonResponse
                    ->setData(401, 'msg.error.your_account_deleted')
                    ->getResponse();
            }

            return $this->createResponse($user, $token);
        }

        // If status is not 200
        return $customJsonResponse
            ->setData(401, 'msg.error.invalid_credentials', $response->json())
            ->getResponse();

    } catch (\Exception $e) {
        \Log::error('Login error:', ['message' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
        return response()->json(['error' => 'An error occurred during login.'], 500);
    }
}


    public function refresh(Request $request)
    {
        $params = [
            'grant_type' => 'refresh_token',
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
            'refresh_token' => $request->json('refresh_token')
        ];

        $request->request->add($params);

        $proxy = Request::create('oauth/token', 'POST');

        return Route::dispatch($proxy);

    }

    public function logout(Request $request)
    {

        if (Auth::check()) {
            Auth::user()->AauthAcessToken()->delete();
        }

    }

    public function social($social, Request $request)
    {
        $verify = null;
        switch ($social) {
            case 'google':
                $service = new GoogleAuthentication();
                $verify = $service->verify();
                break;
            case 'facebook':
                $service = new FacebookAuthentication();
                $verify = $service->verify();
                break;
            default:
                $this->jsonResponse->setData(
                    200,
                    'msg.error.not_found'
                );
                return $this->jsonResponse->getResponse();
        }

        if (!$verify) {
            $this->jsonResponse->setData(
                401,
                'msg.error.invalid_credentials'
            );
            return $this->jsonResponse->getResponse();
        }

        $uiUser = User::where('uid','=',$verify['uid'])->first();


        if ($uiUser) {
            $token = [
                'access_token' => $uiUser->createToken($verify['email'])->accessToken,
                'token_type' => 'Bearer',
                'refreshToken' => 'Bearer'
            ];
            return $this->createResponse($uiUser, $token);
        }

        $mailUser = User::where('email','=',$verify['email'])->first();

        if ($mailUser) {

            $token = [
                'access_token' => $mailUser->createToken($verify['email'])->accessToken,
                'token_type' => 'Bearer',
                'refreshToken' => 'Bearer'
            ];
            if (!$uiUser  && $mailUser) {
                $mailUser->uid = $verify['uid'];
                $mailUser->save();
            }
            return $this->createResponse($mailUser, $token);
        }

        $IP = $request->server->get('REMOTE_ADDR');

        $countryData = (new IpService())->getCountryData($IP);

        $country = Country::where('code', $countryData->country_code)->first();

        if(!$country){

            $country = new Country();

            $country->name_en = $countryData->country;
            $country->name    = $countryData->country;
            $country->flag    = $countryData->flag;
            $country->code    = $countryData->country_code;
            $country->status  = 'active';

            $country->save();

        }

        $user = User::create([
            'username'  => 'optimal_'.random_int(0,9_999_999),
            'email'     => $verify['email'],
            'password'  => Hash::make($verify['uid']),
            'uid'       => $verify['uid'],
            'phone_verify' => false,
            'social_type' => $social,
            'status' => 'pending',
            'country_id' => $country->id //$request->json('country_id')
        ]);

        (new UserPrivacyService())->userPrivacyCreate($user->id);

        $user->userDetails()->create([
            'phone_number' => $request->json('phone_number')
        ]);

        $role = Role::where('name', 'user')->firstOrFail();

        $user->roles()->attach($role);

        $token = [
            'access_token' => $user->createToken($verify['email'])->accessToken,
            'token_type' => 'Bearer',
            'refreshToken' => 'Bearer'
        ];
        return $this->createResponse($user, $token);
    }

    protected function createResponse($user, $token)
    {

        return $data = [
            'user' => $user,
            'token' => $token
        ];
    }


}
