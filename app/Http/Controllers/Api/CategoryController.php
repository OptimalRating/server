<?php

namespace App\Http\Controllers\Api;

use App\Survey;
use App\Country;
use App\Keyword;
use App\Category;
use App\SurveyVote;
use App\SurveyChoice;
use App\Service\IpService;
use App\Service\SmsService;
use Illuminate\Http\Request;
use App\Service\CountryService;
use App\Service\KeywordService;
use Illuminate\Support\Facades\DB;
use App\Service\CustomJsonResponse;
use App\Http\Controllers\Controller;
use App\Validator\CategoryValidator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Service\CacheService;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct(private readonly CustomJsonResponse $jsonResponse)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return array
     */
    public function index()
    {
        if(auth()->user()->hasRole('super_admin')){
            $category = Category::where([
                'parent' => 0,
            ])->get();
            $category =  self::prepareTree($category, request('status', 'active'));
        } else {
            $category = Category::where([
                            'parent' => 0,
                        ])
                                // ->where('status', 'active')
                                ->get();
            $category =  self::prepareTree($category, request('status', 'active'));
        }

        $this->jsonResponse->setData(200,  'msg.info.category.list', $category);
        return $this->jsonResponse->getResponse();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return array
     */
    // public function store(Request $request)
    // {
    //     $requestJsonAll = $request->json()->all();
    //     $requestJsonAll['country_id'] = auth()->user()->country_id;
    //     if(auth()->user()->hasRole('super_admin')) {
    //         $requestJsonAll['country_id'] = null;
    //         $requestJsonAll['code'] = 'category.'.str_slug($requestJsonAll['name']);
    //     }
    //     new KeywordService($requestJsonAll['code'], $requestJsonAll['name'], 'category');
    //     // Log::info('HELLO CODE', ['code' => $requestJsonAll['code']]);
    //        if($requestJsonAll){
    //          // added by Muskan
    //        Keyword::create([
    //        'key' => $requestJsonAll['code'],
    //        'default' => $request->json('name')
    //       ]);
    //     (new CacheService())->keywordCacheCreate(); //
    //     }
    //     $validator = new CategoryValidator();
    //     if($response = $validator->validate()) {
    //         return $response;
    //     }
    //     $category = Category::create($requestJsonAll);
    //     if($category) {
    //         $this->jsonResponse->setData(200,  'msg.info.category.created', $category);
    //         return $this->jsonResponse->getResponse();
    //     }
    //     return $this->categoryError();
    // }
    public function store(Request $request)
{
    $requestJsonAll = $request->json()->all();

    // Assign the authenticated user's country_id, or null for super admins
    $requestJsonAll['country_id'] = auth()->user()->country_id;
    if(auth()->user()->hasRole('super_admin')) {
        $requestJsonAll['country_id'] = null;

        // Generate the category code
        $requestJsonAll['code'] = 'category.' . str_slug($requestJsonAll['name']);
    }

    // Validate the request
    $validator = new CategoryValidator();
    if($response = $validator->validate()) {
        return $response;
    }

    // Create the Category
    $category = Category::create($requestJsonAll);

    // Log the created category for debugging
    Log::info('Created Category:', ['category' => $category]);

    if($category) {
        // Create the Keyword associated with the category
        Keyword::create([
            'key' => $requestJsonAll['code'],
            'default' => $request->json('name'),
            'category_id' => $category->id // Ensure category_id is assigned from the created Category
        ]);

        // Log the created keyword for debugging
        Log::info('Created Keyword with Category ID:', [
            'key' => $requestJsonAll['code'],
            'default' => $request->json('name'),
            'category_id' => $category->id
        ]);

        // Update the cache for keywords
        (new CacheService())->keywordCacheCreate();

        // Return a success response
        $this->jsonResponse->setData(200, 'msg.info.category.created', $category);
        return $this->jsonResponse->getResponse();
    }

    return $this->categoryError();
}

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return array
     */
    public function show($id)
    {
        $category = Category::with(['parent','user'])->where('id','=',$id)->get();

        $this->jsonResponse->setData(200,  'msg.info.success.category.show', $category);
        return $this->jsonResponse->getResponse();
    }

    /**
     * Update the specified resource in storage.
     *
     * @return array|bool
     */
    public function update(Request $request, Category $category)
    {
        $reqAll = $request->except('children');
        
        $validator = new CategoryValidator();

        if($response = $validator->validate()) {
            return $response;
        }

        $category->update($reqAll);
        $this->jsonResponse->setData(200,  'msg.info.success.category.update', $category);
        return $this->jsonResponse->getResponse();
    }

    public function statusUpdate(Request $request, Category $category, SmsService $smsService)
    {
        $reqAll = $request->json()->all();
        $category->update($reqAll);
        $smsService->categoryConfirmMessage($category);

        $this->jsonResponse->setData(200,  'msg.info.category.confirmation', $category);
        return $this->jsonResponse->getResponse();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return array category silme olmamalı bence kategoriye bağlı birçok alt kategori ve
     * category silme olmamalı bence kategoriye bağlı birçok alt kategori ve
     * bunlara bağlı anketler olabilir
     * Passive e alma şeklinde yapılabilir
     */
    public function destroy(Category $category)
    {
        # Delete all category surveys
        $surveys = Survey::where(['category_id' => $category->id])->get();
        if ( $surveys->count() ) {
            $surveys->each(function ($survey, $key) {
                
                # Delete Survey Choice
                $survey_choices = SurveyChoice::where(['survey_id' => $survey->id])->get();
                $survey_choices->each(function ($survey_choice, $key) {
                    $survey_choice->delete();
                });
                
                # Delete Survey Subjects
                DB::table('survey_subjects')
                    ->where('survey_id', $survey->id)
                    ->update(['deleted_at' => DB::raw('NOW()')]);
                
                # Delete Survey Choice
                $survey_votes = SurveyVote::where(['survey_id' => $survey->id])->get();
                $survey_votes->each(function ($survey_vote, $key) {
                    $survey_vote->delete();
                });

                $survey->delete();
            });
        }
        foreach ( $category->getChildren() as $children ) {
            $children->delete();
        }

        $keyword = Keyword::where('key', '=', $category->code);

        $keyword->delete();

        $category->delete();

        $this->jsonResponse->setData(200,  'msg.info.success.category.delete', []);
        return $this->jsonResponse->getResponse();
    }

    public function userCategoryCreate(Request $request) 
    {
        $reqAll = $request->request->all();
        /*get web browser sesstion country data*/
        $curentCountry = Country::where('code',$request->header('country'))->first();

        //if $curentCountry  null
            // is user authorized

        //if has($curentCountry) 
            // is auth()->user()->country_id != $curentCountry->id
                // return not authorized

        /* chech user & session country same or not */
        $headerCountry = $request->header('country');
        // if( ( !empty($curentCountry) && auth()->user()->country_id != $curentCountry->id ) || $headerCountry != 'null') {
        
        if ( $headerCountry != 'null' ) {
            if( auth()->user()->country_id != $curentCountry->id ) {
                // Log::info('HELLO');
                $this->jsonResponse->setData(400,'msg.error_unauthorized_country');
               return $this->jsonResponse->getResponse();
            }
        }

        // dd($curentCountry->id, auth()->user()->country_id);

       if (empty($reqAll['parent'])) {
            $this->jsonResponse->setData(400,  'msg.error.category.not_found');
            return $this->jsonResponse->getResponse();
        }

        $parent = Category::find($reqAll['parent']);

        if (!$parent) {
            $this->jsonResponse->setData(400,  'msg.error.category.not_found');
            return $this->jsonResponse->getResponse();
        }

        // $code = str_slug($reqAll['name']);
        $code = Str::slug($reqAll['name']); //updated

        $is_world = 0;
        $country_id = Auth::user()->country_id;
        if ( $headerCountry == 'null' ) {
            $is_world = 1;
            $country_id = NULL;
        }

        $category = new Category();
        $category->name = $reqAll['name'];
        $category->slug = 'category.'.$code;;
        $category->code = 'category.'.$code;;
        $category->parent = $reqAll['parent'];
        $category->status = 'pending';
        $category->country_id = $country_id;
        $category->user_id = Auth::id();
        $category->is_world = $is_world;
        $category->save();

        $key = Keyword::where('key', '=','category.'.$code);

        $method = 'update';
        if (!$key) {
            $key = new Keyword();
            $method = 'save';
        }

        $key->key = 'category.'.$code;
        $key->default = $reqAll['name'];
        $key->type = 'category';

        /** method dzenlencek */
        // $key->save();

        $this->jsonResponse->setData(200, 'msg.info.category.created_and_pending', $category);
        return $this->jsonResponse->getResponse();

    }

    /**
     * @param $categories
     * @param $status
     * @return array
     */

    private function prepareTree($categories, $status)
    {
        $allCategory = [];
        foreach ($categories as $key => $category) {
            $child = Category::where("parent", $category->id)
                // ->where('status', $status)
                ->PrepareChildrenWhere()
                ->get();

            $category->children = self::prepareTree($child, $status);

            $allCategory[] = $category;//parent::snakeCaseToCamelCase($category);

        }
        return $allCategory;

    }

    /**
     * @return array
     */

    private function categoryError()
    {
        $this->jsonResponse->setData(200, 'msg.error.occured:', []);
        return $this->jsonResponse->getResponse();
    }

    /**
     * @return array
     */
    public function pending(Request $request)
    {
        $categories = Category::where("parent",0)->get();
        $category =  self::prepareTreeForCountryAdmin($categories, $request, 'pending');
        $this->jsonResponse->setData(200,  'msg.info.category.list', $category);

        return $this->jsonResponse->getResponse();

    }

    /**
     * @return array
     */
    public function categoryTree(Request $request)
    {
        $categories = Category::where("parent", 0)->whereStatus('active')->get();

        $categories =  $this->getSubCategories($categories, $request);

        $this->jsonResponse->setData(200,  'msg.info.category.list', $categories);
        return $this->jsonResponse->getResponse();

    }

    /**
     * @param $categories
     * @param $request
     * @return array
     */

    public function getSubCategories($categories, $request)
    {

        $allCategories = [];
        $isThereCountry = true;

        if (auth()->user()){
            $isThereCountry = (!auth()->user()->hasRole('super_admin')|| !auth()->user()->hasRole('country_admin'));
        }

        $country = (new CountryService($request))->getCountry();
        foreach ($categories as $key => $category) {
            $category->children = $category->getChildren('active', $isThereCountry, $country);
            $allCategories[] = $category;
        }

        return $allCategories;
    }

    /**
     * @param $categories
     * @param string $status
     * @return array
     */
    public function prepareTreeForCountryAdmin($categories, Request $request, $status='pending')
    {
        $allCategory = [];
        $countryId = null;

        if (Auth::user() && Auth::user()->country_id) {
            $countryId = Auth::user()->country_id;
        } else {
            $IP = $request->server->get('REMOTE_ADDR');
            $IPService = (new IpService())->getCountryData($IP);
            $country = Country::where('code','=',$IPService->country_code)->first();
            $countryId = $country->id;
        }

        foreach ($categories as $key => $category) {
            $child = Category::where("parent", $category->id)->where('status', $status)->where('country_id',$countryId)->get();
            $category->children = self::prepareTreeForCountryAdmin($child, $request, $status);
            $allCategory[] = $category;//parent::snakeCaseToCamelCase($category)
        }
        return $allCategory;
    }

    public function getChildren(Category $category)
    {
        return $this->jsonResponse->setData(200, 'msg.info.category.list', $category->getChildren())->getResponse();
    }

    public function categoryDetail($category)
    {
        $categories = Category::with(['surveys.choices.votes'])->where("slug",$category)->first();
        return $this->jsonResponse->setData(200, 'msg.info.category.list', $categories)->getResponse();
    }

}

