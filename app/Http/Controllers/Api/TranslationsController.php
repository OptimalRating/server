<?php

namespace App\Http\Controllers\Api;

use App\Country;
use App\CustomObjects\CustomJsonResponse;
use App\Http\Controllers\Controller;
use App\Keyword;
use App\Category;
use App\Service\CacheService;
use App\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\Log;

class TranslationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return array
     */
    public function index()
    {

        $customResponse = new CustomJsonResponse(200,  'msg.info.keyword.list', []);

        return $customResponse->getResponse();
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return array
     */
    public function store(Request $request)
    {

        $auth = Auth::user();
        $country = Country::find($auth->country_id);

        $model = new Translation();

        $model->country_code = $country->code;
        $model->keyword_id = $request->get('id');
        $model->translation = ($request->get('translation'))['translation'];
        Log::info('SAVE Request Data', ['$request' => $request]);

        $model->save();

        (new CacheService())->keywordCacheCreate();

        $customResponse = new CustomJsonResponse(
            200,

            'msg.info.translation.save',
            $model
        );

        return $customResponse->getResponse();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return array
     */
    // public function update(Request $request, Keyword $translation)
    // {
    //     $translation = $translation->translation()->first();
    //     $reqData = $request->get('translation');
    //     Log::info('Translation Request Data:', ['reqData' => $reqData]);
    //     $translation->translation = $reqData['translation'];
    //     $translation->update();
    //     (new CacheService())->keywordCacheCreate();
    //     $customResponse = new CustomJsonResponse(200,  'msg.info.translation.save');
    //     return $customResponse->getResponse();
    // }
    public function update(Request $request, Keyword $translation) //updated code
    {
        // Log::info('All Translation Request Data', ['ALL reqData' => $request]);
    
        // Retrieve the translation from the keyword
        $translation = $translation->translation()->first();
    
        // Get the translation data from the request
        $reqData = $request->get('translation');
    
        // Log the request data
        // Log::info('Translation Request Data', ['reqData' => $reqData]);
    
        // Update the translation field
        $translation->translation = $reqData['translation'];
        $translation->update();
    
        // Update the country_id in the Category model if the condition is met
        $categoryId = $request['category_id'];
        $countryCode = $reqData['country_code'];
    
        // Find the country_id for the given country_code
        $country = Country::where('code', $countryCode)->first();
    
        if ($country) {
            // Find the category where id equals keyword_id
            $category = Category::where('id', $categoryId)->first();
    
            if ($category) {
                // If category found, update the country_id with the corresponding country_id from the countries table
                $category->country_id = $country->id;
                $category->save(); // Save the updated category
    
                Log::info('Category country_id updated', ['category_id' => $category->id, 'new_country_id' => $category->country_id]);
            } else {
                Log::info('Category not found for keyword_id', ['keyword_id' => $categoryId]);
            }
        } else {
            Log::info('Country not found for country_code', ['country_code' => $countryCode]);
        }
    
        // Refresh the keyword cache
        (new CacheService())->keywordCacheCreate();
    
        // Return success response
        $customResponse = new CustomJsonResponse(200, 'msg.info.translation.save');
        return $customResponse->getResponse();
    }
    
    /**
     * @return array
     */
    public function yamlParseKeyword(){

        $yaml = Yaml::parseFile(base_path().'/documentation/message.yml');

        $keyword = Keyword::all();

        foreach ($yaml as $key => $value){

        //  if ($keyword->where('key','=', $key)->count())  continue;

          $pre = explode('.',(string) $key);


          $model = new Keyword();

          $model->key = $key;
          $model->default = $value['tr'];
          $model->type = $pre[0] === 'msg' ? 'messages' : ($pre[0] === 'lbl' ? 'label' : 'input');

          $model->save();
        }

        $customResponse = new CustomJsonResponse(200,  'msg.info.keyword.created');

        return $customResponse->getResponse();
    }
}
