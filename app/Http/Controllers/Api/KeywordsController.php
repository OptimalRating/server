<?php

namespace App\Http\Controllers\Api;

use App\CustomObjects\ApiPagination;
use App\CustomObjects\CustomJsonResponse;
use App\Http\Controllers\Controller;
use App\Keyword;
use App\Service\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class KeywordsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return array
     */
    // public function index()
    // {
    //     $getQuery = Input::all();

    //     $model = Keyword::with('translations')->orderBy($getQuery["sort"],$getQuery["order"])->get();;
    //     $auth = Auth::user();

    //     if($auth->hasRole('country_admin')){

    //         $model = Keyword::with('translation')
    //             ->orderBy($getQuery["sort"],$getQuery["order"])->get();;
    //     }

    //     $pagination = new ApiPagination($getQuery["limit"], $model->count(), $getQuery["offset"]);
    //     $customResponse = new CustomJsonResponse(200,  'msg.info.keyword.list', $model,null, $pagination->getConvertObject());

    //     return $customResponse->getResponse();
    // }
    public function index(Request $request)  //new code 23-10-2025
{
    $getQuery = $request->all();
    $auth = Auth::user();

    $model = Keyword::with(['translations' => function ($query) use ($auth) {
        if ($auth && $auth->hasRole('country_admin')) {
            $country = Country::find($auth->country_id);
            if ($country) {
                // Log::info('if',[$country]);
                $query->where('country_code', $country->code);
            }
        } else if ($auth && $auth->hasRole('super_admin')) {
            // Log::info('else if');
            $query->whereNull('country_code');
        }
        // else{Log::info('only else block');}
    }])->orderBy($getQuery["sort"], $getQuery["order"])->get();
    // Log::info('MODEL==', ['MODEL' =>  $model]);
    $pagination = new ApiPagination($getQuery["limit"], $model->count(), $getQuery["offset"]);
    $customResponse = new CustomJsonResponse(200, 'msg.info.keyword.list', $model, null, $pagination->getConvertObject());

    return $customResponse->getResponse();
}

    // public function index(Request $request)  commented on 23-10-2025
    // {
    //     // Use $request->all() instead of Input::all()
    //     $getQuery = $request->all();

    //     $model = Keyword::with('translations')->orderBy($getQuery["sort"], $getQuery["order"])->get();
    //     $auth = Auth::user();

    //     if ($auth->hasRole('country_admin')) {
    //         $model = Keyword::with('translation')
    //             ->orderBy($getQuery["sort"], $getQuery["order"])->get();
    //     }

    //     $pagination = new ApiPagination($getQuery["limit"], $model->count(), $getQuery["offset"]);
    //     $customResponse = new CustomJsonResponse(200, 'msg.info.keyword.list', $model, null, $pagination->getConvertObject());

    //     return $customResponse->getResponse();
    // }

    /**
     * @return array
     */
    public function store(Request $request)
    {
        //validate the city namne
        $validator = Validator::make($request->json()->all(), [
            'key' => 'required',
            'default' => 'required'
        ]);

        if(!$validator->passes()){
            $customResponse = new CustomJsonResponse(422, 'msg.error.valid','', $validator->errors()->all());
            return $customResponse->getResponse();
        }

        $store = Keyword::create([
            'key' => $request->json('key'),
            'default' => $request->json('default')
        ]);

        (new CacheService())->keywordCacheCreate();

        if($store){
            $customResponse = new CustomJsonResponse(200,  'msg.info.keyword.created', $store);
            return $customResponse->getResponse();
        }

        $customResponse = new CustomJsonResponse(200,  'msg.error.occured:', []);
        return $customResponse->getResponse();
    }

    /**
     * Update the specified resource in storage.
     *
     * @return array
     */
    public function update(Request $request, Keyword $keyword)
    {
        $keyword->key = $request->json('key');
        $keyword->default = $request->json('default');
        $updated = $keyword->update();

        if($updated){
            (new CacheService())->keywordCacheCreate();
            $customResponse = new CustomJsonResponse(200,  'msg.keyword.updated', $keyword);
            return $customResponse->getResponse();
        }
        $customResponse = new CustomJsonResponse(200,  'msg.error.occured:', []);
        return $customResponse->getResponse();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Keyword  $keywords
     * @return \Illuminate\Http\Response
     */
    public function destroy(Keyword $keyword)
    {
        $deleted = $keyword->delete();

        if($deleted){

            (new CacheService())->keywordCacheCreate();

            $customResponse = new CustomJsonResponse(200,  'msg.keyword.deleted', $keyword);
            return $customResponse->getResponse();
        }

        $customResponse = new CustomJsonResponse(200,  'msg.error.occured:', []);
        return $customResponse->getResponse();
    }
}
