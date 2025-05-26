<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Friends;
use App\Privacy;
use App\SmsVerify;
use App\UserToken;
use Carbon\Carbon;
use App\UserDetail;
use App\PrivacyOptions;
use App\UserPrivacySetting;
use App\Service\MailService;
use App\Service\UserService;
use App\Validator\UserValidator;
use App\Service\CustomJsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Validator\UserDetailsVerifyValidator;
use Symfony\Component\HttpFoundation\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use App\Service\UserPrivacyService;
use App\Role;
use App\Country;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    private $customJsonResponse;

    public function __construct(CustomJsonResponse $customJsonResponse)
    {
        $this->customJsonResponse = $customJsonResponse;
    }

//   public function facebookLogin(Request $request)
//     {
//         Log::info('incoming request:', ['$request' => $request]);
//         $token = $request->input('access_token');
//         $email = $request->input('email');
//         $image = $request->input('image');

//         try {
//             $facebookUser = Socialite::driver('facebook')
//                 ->stateless()
//                 ->userFromToken($token);

//             $user = User::updateOrCreate(
//                 ['provider_id' => $facebookUser->getId()],
//                 [
//                     'name' => $facebookUser->getName(),
//                     'email' => $facebookUser->getEmail() ? $facebookUser->getEmail() : $email,
//                     'image' => $image,
//                     'avatar' => $facebookUser->getAvatar(),
//                 ]
//             );

//             $tokenResult = $user->createToken('authToken');

//             return response()->json([
//                 'accessToken' => $tokenResult->accessToken,
//                 'provider' => 'facebook',
//                 'user' => $user,
//             ]);
//         } catch (\Exception $e) {
//             return response()->json([
//                 'error' => 'Facebook login failed',
//                 'message' => $e->getMessage(),
//             ], 500);
//         }
//     }

public function facebookLogin(Request $request)
{
    // Log::info('incoming request:', ['$request' => $request->all()]);

    $token = $request->input('access_token');
    $email = $request->input('email');
    $image = $request->input('image');

    try {
        $facebookUser = Socialite::driver('facebook')
            ->stateless()
            ->userFromToken($token);

        // Merge data
        $userData = [
            'provider_id' => $facebookUser->getId(),
            'name' => $facebookUser->getName(),
            'email' => $facebookUser->getEmail() ?: $email,
            'image' => $image,
            // 'avatar' => $facebookUser->getAvatar(),
        ];

        // (Optional) create a temporary token — note: requires a valid User model if using Sanctum/Passport
        // If you still need a token, you'd need a temporary user or mock that part

        return response()->json([
            'accessToken' => $token, // remove if not creating token
            'provider' => 'facebook',
            'user' => $userData,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Facebook login failed',
            'message' => $e->getMessage(),
        ], 500);
    }
}


    public function socialLogin(Request $request, CustomJsonResponse $customJsonResponse)
{
    Log::info('Hello social');
    Log::info('incoming request=>:', ['$request' => $request]);
    $provider = $request->input('provider');
    $accessToken = $request->input('token');
    $countryCode = $request->input('countryCode');
    $country = Country::where('code', strtoupper($countryCode))->first(); // Make case-insensitive search
    $countryId = null;
    if ($country) {
        $countryId = $country->id;
    }

    try {
        $socialUser = $this->getSocialUser($provider, $accessToken);
    } catch (\Exception $e) {
        return $customJsonResponse->setData(400, 'msg.error.invalid_social_token')->getResponse();
    }

    $name = $socialUser->user->name ?? $socialUser->name ?? $socialUser->attributes->name ?? 'Default Name';
    $email = $socialUser->user->email ?? $socialUser->email ?? $socialUser->attributes->email ?? 'default@example.com';

    $existingUser = User::where('email', $email)->first();

    if ($existingUser) {
    $token = $existingUser->createToken('https://www.optimalrating.com/')->accessToken;
    return $this->createResponse($existingUser, $token);
    }

    $names = explode(" ", $name);
    $firstName = $names[0];
    $lastName = implode(" ", array_slice($names, 1));

    $userData = array(
        'username' => $firstName,
        'name' => $name,
        'firstname' => $firstName,
        'lastname' => $lastName,
        'email' => $email,
        'password' => bcrypt(Str::random(24)),
        'status' => 'approved',
        'country_id' => $countryId,
        'provider' => $provider
    );

    $user = User::firstOrCreate(['email' => $email], $userData);

    // Issue an OAuth token for the user
    $token = $user->createToken('https://www.optimalrating.com/')->accessToken;

    return $this->createResponse($userData, $token);
}

    protected function createResponse($user, $token)
    {
      return $data = [
        'user' => $user,
        'token' => $token
      ];
    }

    private function getSocialUser($provider, $token)
    {
    try {
        if ($provider == 'google') {
            return Socialite::driver('google')->scopes(['openid', 'profile', 'email'])->userFromToken($token);
            // return Socialite::driver('google')->userFromToken($token);
        } elseif ($provider == 'facebook') {
            Log::info('I am in facebook');
            return Socialite::driver('facebook')->userFromToken($token);
        }
        throw new \Exception("Unsupported provider: $provider");
    } catch (\Exception $e) {
        Log::error('Error fetching social user:', ['error' => $e->getMessage()]);
        throw new \Exception("Error fetching user from provider: " . $e->getMessage());
    }
    }

    public function userProfile($username)
    {
        $user_id = auth()->user() ? auth()->user()->id : 0;
        if ( auth()->user() && ( auth()->user()->hasAnyRole(['super_admin', 'country_admin']) || auth()->user()->username == $username ) ) {
            $check_privacy = 0;
            $user = User::with([
                'userDetails',
                'country',
                'city',
            ]);
        } /*elseif ( auth()->user()->username == $username ) {
            $user = User::with([
                'userDetails',
                'country',
                'city'
            ]);
        } */
        else {
            $check_privacy = 1;
            $user = User::with([
                'userDetails',
                'country',
                'city',
                'privacySettings.privacy',
                'privacySettings.privacy.options',
                'privacySettings.option',
            ]);
        }
        $userDetails = $user->where('username', '=', $username)->first();

        // return response()->json($userDetails);

        if ( $check_privacy == 1 ) {
            if ( $userDetails ) {
                foreach ( $userDetails->privacySettings as $privacyInfo ) {
                    $slug = $privacyInfo->privacy->slug;

                    # For User Data
                    if ( isset( $userDetails[$slug] ) ) {
                        switch ( $privacyInfo->option->option ) {
                            case 'Friend':
                                $friend = Friends::hasFriend($userDetails->id, $user_id);
                                if ( !$friend ) {
                                    $userDetails[$slug] = NULL;
                                    if ( $slug == 'country_id' ) {
                                        $userDetails->country->name = NULL;
                                    }
                                    if ( $slug == 'city_id' ) {
                                        $userDetails->city->name = NULL;
                                    }
                                }
                                break;
                            
                            case 'Nobody':
                                $userDetails[$slug] = NULL;
                                if ( $slug == 'country_id' ) {
                                    $userDetails->country->name = NULL;
                                }
                                if ( $slug == 'city_id' ) {
                                    $userDetails->city->name = NULL;
                                }
                                break;
                        }
                    }
                    
                    # For User details data
                    if ( isset( $userDetails->userDetails[$slug] ) ) {
                        switch ( $privacyInfo->option->option ) {
                            case 'Friend':
                                $friend = Friends::hasFriend($userDetails->id, $user_id);
                                if ( !$friend ) {
                                    $userDetails->userDetails[$slug] = NULL;
                                }
                                break;
                            
                            case 'Nobody':
                                $userDetails->userDetails[$slug] = NULL;
                                break;
                        }
                    }
                }
            }
        }

        $this->customJsonResponse->setData(200, 'msg.success.user.view', $userDetails);
        return $this->customJsonResponse->getResponse();
    }

    // public function updateProfile(Request $request)
    // {
    //     $user = \auth()->user();
    //     $userDetails = $user->userDetails()->first();
    //     $req = $request->request->get('user');
    //     Log::info('$userDetails:', ['$userDetails' => $userDetails]);
    //     // if (!$userDetails) {
    //     //     return response()->json(['error' => 'User details not found.'], 404);
    //     // }
        
    //     $userValidator = Validator::make($req, [
    //         'firstname' => 'required',
    //         'lastname' => 'required',
    //         'country_id' => 'required',
    //         'city_id' => 'required'
    //     ]);

    //     $userDetailsValidator = Validator::make($req['user_details'], [
    //         'birthdate' => 'required',
    //         'gender' => 'required',
    //         'education' => 'required',
    //         'city_id' => 'required'
    //     ]);

    //     // if ($userValidator->fails() || $userDetailsValidator->fails()) {
    //     //     return response()->json(['error' => 'Validation failed.'], 422);
    //     // }

    //     $nationalImage = $request->input('user.national_image');
    //     $portraitImage = $request->input('user.portrait_image');

    //     if ($nationalImage) {
    //     $sourcePath = public_path('cdn/images/user/' . $nationalImage);
    //     $destinationPath = public_path('cdn/images/user_nationality/' . $nationalImage);
    //     // Ensure the destination directory exists
    //     if (!file_exists(dirname($destinationPath))) {
    //         mkdir(dirname($destinationPath), 0755, true);
    //     }
    //     // Move the file to the desired location
    //     if (file_exists($sourcePath)) {
    //         copy($sourcePath, $destinationPath);
    //     } else {
    //         return response()->json(['error' => 'Source file for national image not found.'], 404);
    //     }
    //     // Save the new file path to the user model
    //     $user->national_image = 'cdn/images/user_nationality/' . $nationalImage;
    //     }
    //     if ($portraitImage) {
    //     $sourcePath = public_path('cdn/images/user/' . $portraitImage);
    //     $destinationPath = public_path('cdn/images/user_portrait/' . $portraitImage);
    //     // Ensure the destination directory exists
    //     if (!file_exists(dirname($destinationPath))) {
    //         mkdir(dirname($destinationPath), 0755, true);
    //     }
    //     // Move the file to the desired location
    //     if (file_exists($sourcePath)) {
    //         copy($sourcePath, $destinationPath);
    //     } else {
    //         return response()->json(['error' => 'Source file for portrait image not found.'], 404);
    //     }
    //     $user->portrait_image = 'cdn/images/user_portrait/' . $portraitImage;
    //     }
    //     $user->save();

    //     if (!$userValidator->fails() && ($user->status === 'disapproved' || $user->status === 'pendingapproved'))
    //     {
    //         $req['status'] = 'pendingapproved';
    //     }
    //     else
    //     {
    //         if (
    //             !$userValidator->fails()
    //             && !$userDetailsValidator->fails()
    //             && (bool)$user->phone_verify
    //             && $user->status !== 'freeze')
    //         {
    //             $req['status'] = 'approved';
    //         }
    //     }

    //     if (
    //         !is_null($request->request->get('user')['national_image'])
    //         && !is_null($request->request->get('user')['portrait_image'])
    //         && $user->status === 'freeze'
    //     )
    //     {
    //         $req['status'] = 'pendingFreeze';
    //     }

    //     if (!$userValidator->fails() && $user->status == 'pending')
    //     {
    //         $req['status'] = 'approved';
    //     }
    //     unset($req['user_details']['email']);
    //     $userDetails->update($req['user_details']);
    //     $user->update($req);


    //     $this->customJsonResponse->setData(
    //         200,
    //         'msg.success.user.update',
    //         User::where('id', \auth()->id())->with('userDetails')->first()
    //     );
    //     return $this->customJsonResponse->getResponse();
    // }

    public function updateProfile(Request $request)
{
    $user = auth()->user();
    $req = $request->get('user'); // Extract 'user' section of the payload
    $userDetailsPayload = $req['user_details'] ?? null; // Extract 'user_details'

    // Validate user data
    $userValidator = Validator::make($req, [
        'firstname' => 'required|string|max:255',
        'lastname' => 'required|string|max:255',
        'country_id' => 'required|integer',
        'city_id' => 'required|integer',
    ]);

    // Validate user_details data
    $userDetailsValidator = Validator::make($userDetailsPayload, [
        'birthdate' => 'required|date',
        'gender' => 'required|string',
        'education' => 'required|string',
        'phone_number' => 'required|string',
    ]);


    // Update user details
    $userDetails = $user->userDetails()->first();

    if (!$userDetails) {
        // Create a new userDetails record if it doesn't exist
        $userDetails = $user->userDetails()->create($userDetailsPayload);
    } else {
        $userDetails->update($userDetailsPayload); // Update existing record
    }

    // Update user data
    unset($req['user_details']); // Remove user_details from the user payload
    $user->update($req);

    // Return the updated user with userDetails
    return response()->json([
        'message' => 'Profile updated successfully.',
        'data' => User::where('id', $user->id)->with('userDetails')->first(),
    ]);
}

    public function profileImage(Request $request)
    {
        $user = \auth()->user();

        $details = $user->userDetails()->first();


        $details->profile_image = $request->request->get('image');

        $details->save();

        $customResponse = $this->customJsonResponse->setData(200, 'msg.success.profile_image.update', $user);
        return $customResponse->getResponse();
    }

    public function nationalImage(Request $request)
    {
        $user = \auth()->user();

        $user->national_image = $request->request->get('image');
        $user->save();

        $customResponse = $this->customJsonResponse->setData(200, 'msg.success.national_image.update', $user);
        return $customResponse->getResponse();
    }

    public function portraitImage(Request $request)
    {
        $user = \auth()->user();
        $user->portrait_image = $request->request->get('image');
        $user->save();

        $customResponse = $this->customJsonResponse->setData(200, 'msg.success.portrait_image.update', $user);
        return $customResponse->getResponse();
    }

    public function getProfile()
    {
        $user = User::with('userDetails')->find(\auth()->id());
        $this->customJsonResponse->setData(200, 'msg.success.user.view', $user);
        return $this->customJsonResponse->getResponse();
    }

    public function savePhoneNumber()
    {
        $user = \auth()->user();
        $userDetails = $user->userDetails()->first();

        $smsService = SmsVerify::where('phone_number', '=', \request('to'))
            ->where('sms', '=', \request('verify_code'))->first();

        if (!$smsService)
        {
            $this->customJsonResponse->setData(400, 'msg.error.send.verify.sms.error', '', ['error' => 'Sms verify error']);
            return $this->customJsonResponse->getResponse();
        }

        $user->phone_verify = 1;
        $user->save();

        $userDetails->phone_number = \request('to');
        $userDetails->update();

        $this->customJsonResponse->setData(200, 'msg.success.update.phone_number');
        return $this->customJsonResponse->getResponse();
    }

    public function userPrivacySettings()
    {
        $privacies = Privacy::with('options')->oldest('sort')->get();
        
        $user = User::with([
            'privacySettings.privacy',
            'privacySettings.privacy.options',
            'privacySettings.option',
        ])->find(\auth()->id());
        
        // return response()->json($user);

        $response = [
            'user' => $user,
            'privacies' => $privacies,
        ];
        $this->customJsonResponse->setData(200, 'msg.success.user.view', $response);
        return $this->customJsonResponse->getResponse();
    }

    public function userPrivacySettingsChange()
    {
        $user = \auth()->user();
        
        $privacy = UserPrivacySetting::where([
            'privacy_id' => \request('privacy'),
            'user_id' => $user->id,
        ])->first();

        if ( !empty($privacy) && $privacy->user_id != $user->id )
        {
            $this->customJsonResponse->setData(400, 'msg.error.not_privacy');
            return $this->customJsonResponse->getResponse();
        }
        
        if ( !$privacy ) {
            $privacy = new UserPrivacySetting;
            $privacy->privacy_id = \request('privacy');
            $privacy->user_id = $user->id;
        }
        $privacy->option_id = \request('option');
        $privacy->save();

        $this->customJsonResponse->setData(200, 'msg.success.privacy_update', $user->privacySettings);
        return $this->customJsonResponse->getResponse();
    }

    public function passwordChange()
    {
        $user = \auth()->user();
        $user->password = bcrypt(\request('password'));
        $user->save();
        $this->customJsonResponse->setData(200, 'msg.success.password_change');
        return $this->customJsonResponse->getResponse();
    }

    public function checkPassword()
    {
        $user = \auth()->user();

        if (!Hash::check(\request('password'), $user->password))
        {
            $this->customJsonResponse->setData(400, 'msg.error.password');
            return $this->customJsonResponse->getResponse();
        }

        $this->customJsonResponse->setData(200, 'msg.success.correct');
        return $this->customJsonResponse->getResponse();
    }

    public function deleteMyAccount()
    {
        $user = \auth()->user();

        $delete = new UserToken();

        $delete->user = \auth()->id();

        $delete->expire_at = Carbon::now()->addWeek(1);
        $delete->token = md5(Carbon::now()->timestamp);

        $delete->save();

        $mail = new MailService();

        $result = $mail->sendMail($delete->token, $user, 'DeleteProfile', 'delete_profile'); // updated

        $this->customJsonResponse->setData(200, 'msg.success.delete_profile');
        return $this->customJsonResponse->getResponse();
    }

    public function deleteUser($user)
{
    // Log::info('Attempting to delete user with ID:', ['id' => $user->id]);
    
    $user->deleted_at = now();
    $user->email = null;
    if ($user->save()) {
        Log::info('User successfully deleted (soft delete).', ['id' => $user->id]);
    } else {
        Log::error('Failed to delete user.', ['id' => $user->id]);
    }
}

    public function ApproveDeleteProfile($token)
    {
        $deleteToken = UserToken::where('token', '=', $token)->first();
        Log::info('TOKEN FIRST:', ['$deleteToken' => $deleteToken]);
        if (is_null($deleteToken)) {
            Log::info('NULL TOKEN ERROR Occured:');
            $this->customJsonResponse->setData(400, 'Token not found');
            return $this->customJsonResponse->getResponse();
        }
        $user = User::find($deleteToken->user);
        $userDetails = UserDetail::where('user_id',  $user->id)->first();
        if (!is_null($user)) {
            // Soft delete the user
            $userService = new UserService();
            $userService->deleteUser($user);
    
            // Set email to null and save changes
            $user->email = null;
            $user->save();  // Ensure the change is persisted in the database
            if($userDetails){
                if($userDetails->phone_number){
                    $userDetails->phone_number = null;
                }
                $userDetails->save();
            }
            Log::info('User email and phone number has been set to null and user has been soft-deleted.');
        }
    
        Log::info('Profile deletion process completed.');
        $this->customJsonResponse->setData(200, 'Profile has been deleted.');
        return $this->customJsonResponse->getResponse();
    }
    
    public function resetMyPassword()
    {
        $userWithEmail = User::where('email', '=', \request('user'))->first();
        $userWithPhoneNumber = UserDetail::where('phone_number', '=', \request('user'))->first();

        $userWithPhoneNumber = !is_null($userWithPhoneNumber) ? $userWithPhoneNumber->user()->first() : null;

        $user = $userWithEmail ?? $userWithPhoneNumber;

        if (is_null($user))
        {
            $this->customJsonResponse->setData(400, 'msg.error.phone_number_or_email_not_found');
            return $this->customJsonResponse->getResponse();
        }

        $delete = new UserToken();

        $delete->user = $user->id;
        $delete->expire_at = Carbon::now()->addWeek(1);
        $delete->token = md5(Carbon::now()->timestamp);

        $delete->save();

        $mail = new MailService();

        $mail->sendMail($delete->token, $user, 'reset-password', 'reset_password');

        $this->customJsonResponse->setData(200, 'msg.success.sended_reset_password_code');
        return $this->customJsonResponse->getResponse();
    }

    public function newPassword($token)
    {
        $deleteToken = UserToken::where('token', '=', $token)->first();

        if (is_null($deleteToken))
        {
            $this->customJsonResponse->setData(400, 'msg.error.token_error');
            return $this->customJsonResponse->getResponse();
        }

        $user = User::where('id', '=', $deleteToken->user)->first();

        $user->password = bcrypt(\request('password'));

        $user->save();

        $this->customJsonResponse->setData(200, 'msg.success.password_changed');
        return $this->customJsonResponse->getResponse();
    }

    public function verifyEmailChange($token)
    {
        $deleteToken = UserToken::where('token', '=', $token)->first();

        if (is_null($deleteToken))
        {
            $this->customJsonResponse->setData(400, 'msg.error.token_error');
            return $this->customJsonResponse->getResponse();
        }

        $user = User::where('id', '=', $deleteToken->user)->first();

        $user->email = $deleteToken->new_data;

        $user->save();

        $this->customJsonResponse->setData(200, 'msg.success.email_changed');
        return $this->customJsonResponse->getResponse();
    }

    public function emailChange()
    {
        // Log::info('req:', ['req' => \request('email')]);
        if (is_null(\request('email')))
        {
        Log::info('is null');

            $this->customJsonResponse->setData(400, 'msg.error_email_change');
            return $this->customJsonResponse->getResponse();
        }

        if (User::whereEmail(\request('email'))->first())
        {
         Log::info('II:', [User::whereEmail(\request('email'))->first()]);
            $customResponse = $this->customJsonResponse->setData(409, 'msg.error.email_already_used');
            return $customResponse->getResponse();
        }


        $user = \auth()->user();
        $delete = new UserToken();
        $delete->user = $user->id;
        $delete->expire_at = Carbon::now()->addWeek(1);
        $delete->old_data = $user->email;
        $delete->new_data = \request('email');
        $delete->token = md5(Carbon::now()->timestamp);
        $delete->save();


        $mail = new MailService();

        $mail->sendMail($delete->token, $user, 'EmailChange', 'change_email');

        $this->customJsonResponse->setData(200, 'msg.success.sended_email_change_token');
        return $this->customJsonResponse->getResponse();
    }
}
