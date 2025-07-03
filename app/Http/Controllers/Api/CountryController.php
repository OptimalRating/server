<?php

namespace App\Http\Controllers\Api;

use App\City;
use App\Country;
use App\Survey;
use App\Category;
use App\CustomObjects\ApiPagination;
use App\Role;
use App\Service\CustomJsonResponse;
use App\Service\FakeService;
use App\Service\IpService;
use App\Service\LanguagesService;
use App\User;
use App\Validator\CountryValidator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; 
use Mockery\Exception;

class CountryController extends Controller
{
    /**
     * @var CustomJsonResponse
     */
    private $jsonResponse;

    public function __construct(CustomJsonResponse $jsonResponse)
    {
        $this->jsonResponse = $jsonResponse;
    }

    public function index()
    {
    $model = Country::with(['user'])
        ->whereNull('deleted_at') // Add this line to filter out deleted countries
        ->orderBy(request("sort", 'id'), request("order", 'desc'))
        ->get();

    $pagination = new ApiPagination(request("limit", 20), count($model), request("offset", 0));

    $this->jsonResponse->setData(200,
        'msg.info.success.country.list', $model, null, $pagination->getConvertObject());

    return $this->jsonResponse->getResponse();
    }


    public function store(Request $request)
    {

        $validator = new CountryValidator();

        if ($response = $validator->validate())
            return $response;


        //create country admin

        if (!$countryAdmin = User::where('email', $request->json('email'))->first())
        {
            $countryAdmin = new User();
            $countryAdmin->email = $request->json('email');
            $countryAdmin->username = str_replace(' ', '_', (string) $request->json('name_en')). '_admin';
            $countryAdmin->password = Hash::make($request->json('password'));
            $countryAdmin->save();
            //    $countryAdmin = User::create([
            //        'email' => $request->json('email'),
            //        'username' => $request->json('name_en') . '_admin',
            //        'password' => Hash::make($request->json('password')),
            //        'country_id' => $country->id
            //    ]);
            $data = $request->json()->all();

            $data['country_admin'] = $countryAdmin->id;


            $country = Country::create($data);
            $countryAdmin->country_id = $country->id;
            $countryAdmin->save();
        }
        else
        {

            $countryAdmin->country_id = $country->id;

            $countryAdmin->update();
        }

        $role_country_admin = Role::where('name', 'country_admin')->first();

        $countryAdmin->roles()->attach($role_country_admin);

        (new FakeService())->createUser($country);   

        // Log::info('Country====>', ['Country Admin' => $countryAdmin]);

        return response()->json([
            'code' => 200,
            'message' => 'msg.info.country.add',
        ]);
    }

    public function show(Country $country)
    {
        try
        {
            return response()->json([
                'code' => 200,
                'message' => 'msg.info.country.show',
                'data' => [
                    "set" => $country
                ]
            ]);
        }
        catch (Exception)
        {
            return $this->jsonResponse->setData(200, 'msg.error.not_found')->getResponse();
        }
    }

    public function update(Request $request, Country $country)
    {
        //$validator = new CountryValidator();

      //        if($response = $validator->validate())
      //            return $response;

        $country->update(request()->json()->all());

        if (!is_null($request->json('password')))
        {
            $user = $country->user()->first();
            $user->password = bcrypt($request->json('password'));
            $user->save();
        }

        $this->jsonResponse->setData(200, 'msg.info.success.country.update', []);
        return $this->jsonResponse->getResponse();

    }

    //country silme olmamalı bence bu ülkeye bağlı bir
    //sürü anket user vs data olacağından bu işlem sorunlar doğuracaktır.
    // Passive e alma şeklinde yapılabilir
    // public function destroy(Request $request, Country $country)
    // {
    //     // Step 1: Delete all surveys related to this country
    //     $surveys = Survey::where('country_id', $country->id)->whereNull('deleted_at')->get();
    //     foreach ($surveys as $survey) {
    //         $survey->delete(); // Soft delete the survey
    //     }
    
    //     // Step 2: Delete all categories associated with the country
    //     $categories = Category::where('country_id', $country->id)->whereNull('deleted_at')->get();
    //     foreach ($categories as $category) {
    //         // First delete the surveys linked to the category (if any)
    //         Survey::where('category_id', $category->id)->delete(); // This ensures category's surveys are removed
    
    //         // Now delete the category
    //         $category->delete();
    //     }
    
    //     // Step 3: Soft delete all users related to the country
    //     $users = User::where('country_id', $country->id)->whereNull('deleted_at')->get();
    //     foreach ($users as $user) {
    //         $user->delete(); // Soft delete the user
    //     }
    //     // Step 4: Finally, delete the country
    //     $country->delete();
    
    //     return $this->jsonResponse->setData(200, 'msg.info.success.country.delete')->getResponse();
    // }

     public function destroy(Request $request, Country $country) //modify whole for permanent delete 3rd July
{
    // Step 1: Permanently delete all surveys related to this country
    $surveys = Survey::where('country_id', $country->id)->get();
    foreach ($surveys as $survey) {
        $survey->forceDelete(); // Force delete survey
    }

    // Step 2: Permanently delete all categories associated with the country
    $categories = Category::where('country_id', $country->id)->get();
    foreach ($categories as $category) {
        // First permanently delete surveys linked to the category (if any)
        Survey::where('category_id', $category->id)->forceDelete();

        // Now permanently delete the category
        $category->forceDelete();
    }

    // Step 3: Permanently delete all users related to the country
    $users = User::where('country_id', $country->id)->get();
    foreach ($users as $user) {
        $user->forceDelete(); // Force delete user
    }

    // Step 4: Permanently delete the country itself
    $country->forceDelete();

    return $this->jsonResponse->setData(200, 'msg.info.success.country.delete')->getResponse();
}

    
    // public function destroy(Request $request, Country $country)
    // {
    //     $country->delete();
    //     // $country->update(['status'=>'passive']);
    //     return $this->jsonResponse->setData(200, 'msg.info.success.country.delete')->getResponse();
    // }
    /**
     * @return array
     */
    public function citiesOfCountry(Country $country)
    {
        $model = City::where('country_id', '=', $country->id)->get();

        $this->jsonResponse->setData(200,
            'msg.info.success.city.list',
            $model,
            null,
            null);

        return $this->jsonResponse->getResponse();
    }

    /**
     * for country languages without permission
     */
    public function languages(Request $request)
{
    // Fetch countries where deleted_at is null
    $countries = Country::whereNull('deleted_at')
        ->orderBy('name')
        ->select('id', 'name', 'flag', 'name_en', 'code')
        ->get();
 
    // Get the user's IP address
    $IP = $request->server->get('REMOTE_ADDR');

    // Get country data based on IP address
    $IPService = (new IpService())->getCountryData($IP);

    // Return the response with the list of countries
    return $this->jsonResponse->setData(200, 'msg.info.success.country.list', $countries)->getResponse();
}

}
