<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Survey;
use App\Country;
use App\Friends;
use App\Category;
use Carbon\Carbon;
use App\SurveyVote;
use App\SurveyChoice;
use App\Comment;
use App\Helper\CustomHelper;
use Illuminate\Http\Request;
use App\Service\CountryService;
use App\Validator\SurveyValidator;
use App\Service\CustomJsonResponse;
use App\CustomObjects\ApiPagination;
use App\Http\Controllers\Controller;
use App\Repositories\SurveyRepository;
use App\Validator\SurveyVoteValidator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SurveysController extends Controller
{
    /**
     * @var CustomJsonResponse
     */
    private $jsonResponse;

    public function __construct(CustomJsonResponse $jsonResponse)
    {
        $this->jsonResponse = $jsonResponse;
    }
    /**
     * List of survey collection
     *
     * @param string $type
     * @param Request $request
     * @return array
     */
    public function index(Request $request, $type = 'normal')
    {

        if ( $type == 'normal' ) {
            $model = Survey::whereHas('category', fn($query) => $query->where(['status' => 'active']))
            ->with(['choices.votes', 'category']);
        } else {
            $model = Survey::with(['choices.votes', 'category']);
        }
        $model->where('type', $type);

        if (request('year')) {
            $model->whereMonth('start_at', request('year'));
        }

        if (request('month')) {
            $model->whereMonth('start_at', request('month'));
        }

        if (request('status') !== null) {
            $model->where('surveys.status', '=', request('status', 0));
        }

        if (request('category') !== null) {
            $model->where('surveys.category_id', request('category'));
        }


        if (auth()->user()->hasRole('country_admin')) {
            $model->where('country_id', auth()->user()->country_id)->where('is_world', false);
        } else {
            if ($type == 'normal') {
                $model->where('surveys.is_world', true);
            } else {
                $model->where('surveys.country_id', null);
            }
        }

        // else {
        //     $model->where('country_id', null);
        // }

        // $country = (new CountryService($request))->getCountry();	

        // if(auth()->user()->hasRole('country_admin')){	
        //     $model->where('country_id', '=', $country ? $country->id : null);	
        // }
        // Log::info("Model Data", [$model->get()->toArray()]);

        $count = $model->count();
        $pagination = new ApiPagination(request("limit", 20), $count, request("offset", 0));

        $this->jsonResponse->setData(
            200,
            'msg.info.list.surveys',
            $model->get(),
            null,
            $pagination->getConvertObject()
        );

        return $this->jsonResponse->getResponse();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return array
     */
    public function store(Survey $survey, Request $request)
    {
        $validator = new SurveyValidator();

        if ($response = $validator->validate())
            return $response;

        if (request('type') == 'special' && !auth()->user()->hasAnyRole(['super_admin', 'country_admin'])) {
            return $this->surveyError('You are not allowed to add special survey');
        }


        if (!$request->is_world && !auth()->user()->hasAnyRole(['super_admin', 'country_admin'])) {
            $country = Country::where('code', $request->country_code)->first();
            if ($country->id != auth()->user()->country_id) {
                return $this->jsonResponse->setData(400, 'msg.error_not_allowed_country', $survey)->getResponse();
            }
        }

        $survey = $this->saveSurvey($request);

        //store the choices

        $this->addSurveyChoices($survey, $request, null);

        if ($survey) {
            // add survey subject
            if ($subjects = request('subjects')) {
                foreach ($subjects as $subject)
                    $survey->subjects()->attach($subject);
            }
            $this->jsonResponse->setData(200, 'msg.info.survey.created', $survey);
            return $this->jsonResponse->getResponse();
        }
    }

    /**
      * @param $id
      * @return array
      */
     public function getdetails(Request $request, $slug)
{
    // Set the take parameter, default to 3
    $take = $request->take ?? 3;
    $survey_list_count = 0;

    // Retrieve the survey with soft-deleted users
    $survey = Survey::withTrashed()->where('slug', $slug)->first();

    // Check if the survey exists
    if (!$survey) {
        return response()->json([
            'status' => 404,
            'message' => 'Survey not found'
        ], 404);
    }

    $id = $survey->id;

    $surveyType = $request->get('surveyType', 'normal'); //added by muskan
    $votesRelation = $surveyType === 'normal' ? 'votes' : 'votesSpecial'; //added by muskan

    // Get the survey choices with or without pagination
    if (isset($request->pagination)) {
        $survey_list_count = SurveyChoice::with('votes')
            ->withTrashed()
            ->where('survey_id', $id)
            ->where('status', 1)
            ->count();
    $survey_list = SurveyChoice::with([$votesRelation => function ($query) {
    $query->withTrashed();
}]) //updated by Muskan
        // $survey_list = SurveyChoice::with(['votes' => function ($query) {
        //         $query->withTrashed();
        //     }]) //commented by muskan
            ->withTrashed()
            ->where('survey_id', $id)
            ->where('status', 1)
            ->limit($take)
            ->offset($request->offset);
    } else {
        $survey_list = SurveyChoice::with(['votes' => function ($query) {
                $query->withTrashed();
            }])
            ->withTrashed()
            ->where('survey_id', $id);
    }

    // Get the survey details with relationships, including soft-deleted comments
    $model = Survey::with([
        // 'choices.votesSpecial',
        // 'choices.votes',
        "choices.$votesRelation", //updated by muskan
        'subjects',
        'comments' => function ($query) {
            // $query->withTrashed()->with([ // 16-06-25
                $query->with([
                'comments.user.userDetails', 
                'user.userDetails', 
                'likes.user'
            ]);
        },
        'user.userDetails'
    ])
    ->withTrashed()  // Include soft-deleted surveys
    ->whereNotNull('category_id') // 17-06-25
    ->where('id', $id);

    $pagination = new ApiPagination(request("limit", $take), $survey_list_count, request("offset", 0));

    // If the user is authenticated but not an admin, filter by status
    if (auth()->user() && !auth()->user()->hasAnyRole(['country_admin', 'super_admin'])) {
        $model->where('status', '=', 1);
    }

    $model = $model->first();
    if ($model) {
        $model->choices = $survey_list->get();

        // Add survey_votes and isImageUpdated field
        foreach ($model->choices as $key => $choice) {
            $vote = SurveyVote::where('choice_id', $choice->id)
                ->withTrashed()
                ->selectRaw('sum(mark) as total_votes')
                ->groupBy('choice_id')
                ->first();

            $choice->survey_votes = !empty($vote) ? $vote->total_votes : 0;
            $choice->isImageUpdated = (bool) $choice->isImageUpdated;
        }

        $this->jsonResponse->setData(
            200,
            'msg.info.list.surveys',
            $model,
            null,
            $pagination->getConvertObject()
        );

        return $this->jsonResponse->getResponse();
    } else {
        return response()->json([
            'status' => 404,
            'message' => 'Survey details not found'
        ], 404);
    }
}

        
    public function detail(Request $request, $id)
    {
        if (isset($request->take)) {
            $take = $request->take;
        } else {
            $take = 3;
        }
        $survey_list_count = 0;
        if (isset($request->pagination)) {
            // $model = SurveyChoice::with('votes')->where('survey_id', $id)->where('status', 1);
            // $survey_list_count = SurveyChoice::withTrashed()->with('votes')->where('survey_id', $id)->where('status', 1)->count();//16-06-25
            $survey_list_count = SurveyChoice::with('votes')->where('survey_id', $id)->where('status', 1)->count();
            // $survey_list = SurveyChoice::withTrashed()->with('votes')->where('survey_id', $id)->where('status', 1)->limit($take)->offset($request->offset);
            $survey_list = SurveyChoice::with('votes')->where('survey_id', $id)->where('status', 1)->paginate($take);
        } else {
            // $model = SurveyChoice::with('votes')->where('survey_id', $id);
            // $survey_list = SurveyChoice::withTrashed()->with('votes')->where('survey_id', $id); //16-06-25
            $survey_list = SurveyChoice::with('votes')->where('survey_id', $id);
        }

        // $model = Survey::withTrashed()->with([ //16-06-25
            $model = Survey::with([
            // 'choices.votes',
            'subjects',
            'comments' => function ($query) {
            // $query->withTrashed()->with([//16-06-25
            $query->with([
                'comments.user.userDetails', 
                'user.userDetails', 
                'likes.user'
            ]);
        },
            // 'comments.comments.user.userDetails',
            // 'comments.user.userDetails',
            // 'comments.likes.user',
            'user.userDetails'
        ])
        // ->withTrashed() //16-06-25
        ->where('id', $id);
        //            ->where('mark', '!=', null);


        $pagination = new ApiPagination(request("limit", $take), $survey_list_count, request("offset", 0));
        // $pagination = new ApiPagination(request("limit", 20), count($model->get()), request("offset", 0));

        if (auth()->user() && !auth()->user()->hasAnyRole(['country_admin', 'super_admin'])) {
            $model->where('status', '=', 1);
        }


        $model = $model->first();
        if ( $model ) {
            $model->choices = $survey_list->get();
        }

        $this->jsonResponse->setData(
            200,
            'msg.info.list.surveys',
            $model,
            null,
            // null,
            $pagination->getConvertObject()
        );

        return $this->jsonResponse->getResponse();
    }

    /**
     * @return array
     */
    public function update(Survey $survey, Request $request)
    {
        // return response()->json($request->all());
        $surveyRepository = new SurveyRepository();
        
        $fillable = request()->only($survey->getFillable());
        
        $fillable["start_at"] = date("Y-m-d H:i:s", strtotime((string) $fillable["start_at"]));
        $fillable["expire_at"] = date("Y-m-d H:i:s", strtotime((string) $fillable["expire_at"]));
        
        // return response()->json( $fillable );
        $update = $surveyRepository->updateData($survey, $fillable);

        if ($update) {
            //update the choices
            $this->addSurveyChoices($survey, $request, 'update');
            if ($subjects = request('subjects')) {
                $survey->subjects()->sync($subjects);
            }

            $this->jsonResponse->setData(
                200,
                'msg.info.list.surveys',
                $survey,
                null,
                null
            );
        }

        return $this->jsonResponse->getResponse();
    }

    public function pushVote()
    {
        if ( CustomHelper::isUserAuthorized() ) {
            return $this->jsonResponse->setData(400, '', '','msg.must_approved')->getResponse();
        }
        
        $validator = new SurveyVoteValidator();

        if ($response = $validator->validate())
            return $response;

        $vote = SurveyVote::where('survey_id', '=', request('survey_id'))
            ->where('user_id', '=', auth()->id())->first();


        if (!is_null($vote) && $vote->choice_id == request('choice_id')) {
            $this->jsonResponse->setData(200, 'msg.info.survey.vote.already');
            return $this->jsonResponse->getResponse();
        }


        if (!is_null($vote)) {
            $vote->choice_id = request('choice_id');
            $vote->update();
        } else {

            $vote = new SurveyVote();
            $vote->survey_id = request('survey_id');
            $vote->choice_id = request('choice_id');
            $vote->country_id = auth()->user()->country_id;
            $vote->user_id = auth()->id();

            $vote->save();
        }

        $this->jsonResponse->setData(200, 'msg.info.survey.vote.success');
        return $this->jsonResponse->getResponse();
    }



public function homeCurrentSpecialSurvey(Request $request)
{
    $headerCountry = $request->header('country');
    $country_id = null;
    $is_world = 1;

    if ($headerCountry !== 'null') {
        $curentCountry = Country::select(['id', 'code'])->where('code', $headerCountry)->first();

        // Check if $curentCountry is a valid object
        if ($curentCountry) {
            $country_id = $curentCountry->id;
            $is_world = 0;
        } else {
            $is_world = 1;
            // Handle case where the country is invalid, if necessary.
        }
    }

    $now = (new Carbon());
    $user_id = auth()->user() ? auth()->user()->id : 0;

    // Fetch the list of surveys using get() instead of first()
    // $models = Survey::with([
        $model = Survey::with([
            'choices.votesSpecial',
            'subjects',
            'comments' => function ($query) {
                $query->withTrashed(); // Include soft-deleted comments
            },
            'comments.comments.likes',
            'comments.comments.user.userDetails',
            'comments.user.userDetails',
            'comments.likes.user',
            'comments.user.privacySettings.privacy',
            'comments.user.privacySettings.privacy.options',
            'comments.user.privacySettings.option',
            'comments.user.country',
            'comments.user.city',
        ])
        ->where('show_on_home', 1)
        ->where('type', '=', 'special')
        ->where('expire_at', '>', $now)
        ->where('start_at', '<=', $now)
        ->where('country_id', $country_id)
        ->where('is_world', $is_world)
        ->orderBy('expire_at', 'asc')
        ->withTrashed()
        ->whereHas('user', function ($query) {
            $query->withTrashed(); // Ensure that soft-deleted users are included
        })
        ->first();

    // Process each survey's comments and privacy settings
    // if ($models) {
        if ($model) {
        // foreach ($models as $model) {
            foreach ($model->comments as $key => $comment) {
                foreach ($comment->user->privacySettings as $privacyInfo) {
                    $slug = $privacyInfo->privacy->slug;
                    $userDetails = $comment->user;

                    // For User Data
                    if (isset($userDetails[$slug])) {
                        switch ($privacyInfo->option->option) {
                            case 'Friend':
                                // $friend = Friends::withTrashed()->hasFriend($userDetails->id, $user_id);
                                $friend = Friends::hasFriend($userDetails->id, $user_id);
                                if (!$friend) {
                                    $userDetails[$slug] = NULL;
                                    if ($slug == 'country_id') {
                                        $userDetails->country->name = NULL;
                                    }
                                    if ($slug == 'city_id') {
                                        $userDetails->city->name = NULL;
                                    }
                                }
                                break;

                            case 'Nobody':
                                $userDetails[$slug] = NULL;
                                if ($slug == 'country_id') {
                                    $userDetails->country->name = NULL;
                                }
                                if ($slug == 'city_id') {
                                    $userDetails->city->name = NULL;
                                }
                                break;
                        }
                    }

                    // For User details data
                    if (isset($userDetails->userDetails[$slug])) {
                        switch ($privacyInfo->option->option) {
                            case 'Friend':
                                // $friend = Friends::withTrashed()->hasFriend($userDetails->id, $user_id);
                                $friend = Friends::hasFriend($userDetails->id, $user_id);
                                if (!$friend) {
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

            // Prepare the comments as a tree
            $model->comments = self::prepareTree($model->comments);
        // }
    }

    // Return the surveys as an array (a collection of surveys)
    $this->jsonResponse->setData(
        200,
        'msg.info.list.surveys',
        // $models,
        $model,
        null,
        null
    );

    return $this->jsonResponse->getResponse();
}

    public function homeSpecialSurvey(Request $request)
{
    $headerCountry = $request->header('country');
    $country_id = null;
    $is_world = 1;

    if ($headerCountry !== 'null') {
        $curentCountry = Country::select(['id', 'code'])->where('code', $headerCountry)->first();

        // Check if $curentCountry is a valid object
        if ($curentCountry) {
            $country_id = $curentCountry->id;
            $is_world = 0;
        } else {
            $is_world = 1;
            // Handle case where the country is invalid, if necessary.
        }
    }

    $now = (new Carbon());
    $user_id = auth()->user() ? auth()->user()->id : 0;

    // Fetch the list of surveys using get() instead of first()
    $models = Survey::with([
            'choices.votesSpecial',
            'subjects',
            'comments' => function ($query) {
                $query->withTrashed(); // Include soft-deleted comments
            },
            'comments.comments.likes',
            'comments.comments.user.userDetails',
            'comments.user.userDetails',
            'comments.likes.user',
            'comments.user.privacySettings.privacy',
            'comments.user.privacySettings.privacy.options',
            'comments.user.privacySettings.option',
            'comments.user.country',
            'comments.user.city',
        ])
        ->where('show_on_home', 1)
        ->where('type', '=', 'special')
        ->where('expire_at', '>', $now)
        ->where('start_at', '<', $now)
        ->where('country_id', $country_id)
        ->where('is_world', $is_world)
        ->orderBy('id', 'desc')
        ->withTrashed()
        ->whereHas('user', function ($query) {
            $query->withTrashed(); // Ensure that soft-deleted users are included
        })
        ->get(); // Use get() to return a collection of surveys

    // Process each survey's comments and privacy settings
    if ($models) {
        foreach ($models as $model) {
            foreach ($model->comments as $key => $comment) {
                foreach ($comment->user->privacySettings as $privacyInfo) {
                    $slug = $privacyInfo->privacy->slug;
                    $userDetails = $comment->user;

                    // For User Data
                    if (isset($userDetails[$slug])) {
                        switch ($privacyInfo->option->option) {
                            case 'Friend':
                                $friend = Friends::withTrashed()->hasFriend($userDetails->id, $user_id);
                                if (!$friend) {
                                    $userDetails[$slug] = NULL;
                                    if ($slug == 'country_id') {
                                        $userDetails->country->name = NULL;
                                    }
                                    if ($slug == 'city_id') {
                                        $userDetails->city->name = NULL;
                                    }
                                }
                                break;

                            case 'Nobody':
                                $userDetails[$slug] = NULL;
                                if ($slug == 'country_id') {
                                    $userDetails->country->name = NULL;
                                }
                                if ($slug == 'city_id') {
                                    $userDetails->city->name = NULL;
                                }
                                break;
                        }
                    }

                    // For User details data
                    if (isset($userDetails->userDetails[$slug])) {
                        switch ($privacyInfo->option->option) {
                            case 'Friend':
                                $friend = Friends::withTrashed()->hasFriend($userDetails->id, $user_id);
                                if (!$friend) {
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

            // Prepare the comments as a tree
            $model->comments = self::prepareTree($model->comments);
        }
    }

    // Return the surveys as an array (a collection of surveys)
    $this->jsonResponse->setData(
        200,
        'msg.info.list.surveys',
        $models, // This now returns an array of surveys
        null,
        null
    );

    return $this->jsonResponse->getResponse();
}

    public function homeSpecialSurveyById(Request $request, $id)
    {
        $country = (new CountryService($request))->getCountry();
        $now = (new Carbon());
        $user_id = auth()->user() ? auth()->user()->id : 0;

        $survey = Survey::where([
        	'id' => $id,
        	'type' => 'special',
        	'country_id' => $country ? $country->id : null,
        ])
    	->where('expire_at', '>', $now->toDateTimeString())
        ->where('start_at', '<', $now->toDateTimeString())
        ->withTrashed()
        ->get();
        // ->first();

        // return response()->json($survey);

        $model = Survey::with([
            'choices.votesSpecial',
            'subjects',
            'comments' => function ($query) {
        $query->withTrashed(); // Include soft-deleted comments
         },
            'comments.comments.likes',
            'comments.comments.user.userDetails',
            'comments.user.userDetails',
            'comments.likes.user',
            'comments.user.privacySettings.privacy',
            'comments.user.privacySettings.privacy.options',
            'comments.user.privacySettings.option',
            'comments.user.country',
            'comments.user.city',
        ])
            ->where('id', $id)
            ->where('type', '=', 'special')
            // ->where('expire_at', '>', $now)
            // ->where('start_at', '<', $now)
            // ->whereIn('country_id', [auth()->user()->country_id, null])
            ->where('country_id', $country ? $country->id : null)
            ->orderBy('id', 'desc')
            ->withTrashed()
            ->whereHas('user', function ($query) {
                $query->withTrashed(); // Ensure that soft-deleted users are included
            })
            // ->get();
            ->first();

        if ($model) {

            foreach ($model->comments as $key => $comment) {
                foreach ($comment->user->privacySettings as $privacyInfo) {
                    $slug = $privacyInfo->privacy->slug;
                    $userDetails = $comment->user;
                    # For User Data
                    if (isset($userDetails[$slug])) {
                        switch ($privacyInfo->option->option) {
                            case 'Friend':
                                $friend = Friends::withTrashed()->hasFriend($userDetails->id, $user_id);
                                if (!$friend) {
                                    $userDetails[$slug] = NULL;
                                    if ($slug == 'country_id') {
                                        $userDetails->country->name = NULL;
                                    }
                                    if ($slug == 'city_id') {
                                        $userDetails->city->name = NULL;
                                    }
                                }
                                break;

                            case 'Nobody':
                                $userDetails[$slug] = NULL;
                                if ($slug == 'country_id') {
                                    // dd($userDetails->country);
                                    $userDetails->country->name = NULL;
                                }
                                if ($slug == 'city_id') {
                                    $userDetails->city->name = NULL;
                                }
                                break;
                        }
                    }

                    # For User details data
                    if (isset($userDetails->userDetails[$slug])) {
                        switch ($privacyInfo->option->option) {
                            case 'Friend':
                                $friend = Friends::withTrashed()->hasFriend($userDetails->id, $user_id);
                                if (!$friend) {
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
            $model->comments = self::prepareTree($model->comments);
        }


        $this->jsonResponse->setData(
            200,
            'msg.info.list.surveys',
            $model,
            null,
            null
        );

        return $this->jsonResponse->getResponse();
    }

    public function homeSurveyApproval(Request $request)
    {
    $country = Country::where('code', request('country'))->first();
    $user_id = auth()->user() ? auth()->user()->id : 0;
    $vote = SurveyVote::withTrashed()->groupBy('survey_id')
        ->selectRaw('survey_id, sum(mark) as sum')
        ->orderBy('sum', 'desc')
        ->where(function ($q) use ($country) {
            $q->whereHas('survey', function ($subQ) use ($country) {
                if ($country) {
                    $subQ->where('is_world', false);
                } else {
                    $subQ->where('is_world', true);
                }
            });

            if ($country) {
                $q->where('country_id', $country->id);
            }
        })
        ->first();

    if (!$vote) {
        $this->jsonResponse->setData(
            200,
            'msg.warning.survey.not_found',
            null,
            null,
            null
        );

        return $this->jsonResponse->getResponse();
    }

    $model = Survey::with([
        'subjects',
        'comments' => function ($query) {
        $query->withTrashed(); // Include soft-deleted comments
         },
        'comments.comments.user.userDetails',
        'comments.user.userDetails',
        'comments.comments.likes',
        'comments.likes.user',
        'comments.user.privacySettings.privacy',
        'comments.user.privacySettings.privacy.options',
        'comments.user.privacySettings.option',
        'comments.user.country',
        'comments.user.city',
    ])
        ->where('status', '=', true)
        ->where('type', '=', 'normal')
        ->whereNotNull('category_id')
        ->withTrashed() // Include surveys from soft-deleted users
        ->whereHas('user', function ($query) {
            $query->withTrashed(); // Ensure that soft-deleted users are included
        });

    if (!is_null($vote)) {
        $model->where('id', $vote->survey_id);
    }

    $model = $model->first();

    if ($model) {
        foreach ($model->comments as $key => $comment) {
            foreach ($comment->user->privacySettings as $privacyInfo) {
                $slug = $privacyInfo->privacy->slug;
                $userDetails = $comment->user;

                // Privacy settings for user details
                if (isset($userDetails[$slug])) {
                    switch ($privacyInfo->option->option) {
                        case 'Friend':
                            // $friend = Friends::withTrashed()->hasFriend($userDetails->id, $user_id);
                            $friend = Friends::hasFriend($userDetails->id, $user_id);
                            if (!$friend) {
                                $userDetails[$slug] = NULL;
                                if ($slug == 'country_id') {
                                    $userDetails->country->name = NULL;
                                }
                                if ($slug == 'city_id') {
                                    $userDetails->city->name = NULL;
                                }
                            }
                            break;

                        case 'Nobody':
                            $userDetails[$slug] = NULL;
                            if ($slug == 'country_id') {
                                $userDetails->country->name = NULL;
                            }
                            if ($slug == 'city_id') {
                                $userDetails->city->name = NULL;
                            }
                            break;
                    }
                }

                // Privacy settings for user details
                if (isset($userDetails->userDetails[$slug])) {
                    switch ($privacyInfo->option->option) {
                        case 'Friend':
                            // $friend = Friends::withTrashed()->hasFriend($userDetails->id, $user_id);
                            $friend = Friends::hasFriend($userDetails->id, $user_id);
                            if (!$friend) {
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

        $survey_lists = SurveyChoice::withTrashed()->with('votes')->where('survey_id', $model->id)->take(10)->get();

        foreach ($survey_lists as $key => $survey) {
            $vote = SurveyVote::withTrashed()->groupBy('choice_id')
                ->selectRaw('sum(mark) as total_votes')
                ->where('choice_id', $survey->id);
            if ($request->orderBy == 'date') {
                $vote->orderBy('created_at', 'desc');
            } else {
                $vote->orderBy('total_votes', 'desc');
            }
            $vote = $vote->first();
            $survey->survey_votes = !empty($vote) ? $vote->total_votes : 0;
        }

        $model->comments = self::prepareTree($model->comments);
        $model->choices = $survey_lists;
    }

    $this->jsonResponse->setData(
        200,
        'msg.info.list.surveys',
        $model,
        null,
        null
    );

    return $this->jsonResponse->getResponse();
}


    private function prepareTree($comments)
    {
        $allComments = [];
        foreach ($comments as $key => $comment) {
            self::prepareTree($comment->comments);
            $allComments[] = $comment;
        }

        return $allComments;
    }

    private function addSurveyChoices($survey, $request, $type = null)
    {
        if ($survey) {
            foreach (request()->json('choices') as $choice) {

                if (is_null($choice['id']) || $choice['id'] == "") {
                    $choiceSaved = SurveyChoice::create([
                        'choice_title' => $choice['choice_title'],
                        'survey_id' => $survey->id ?? request('id'),
                        'choice_image' => !empty($choice['choice_image']) ? $choice['choice_image'] : null,
                        'choice_description' => $choice['choice_description'],
                        'survey_type' => $survey->type
                    ]);
                } else {
                    $choiceSaved = SurveyChoice::find($choice['id']);
                    $choiceSaved->choice_title = $choice['choice_title'];
                    $choiceSaved->choice_description = $choice['choice_description'];
                    $choiceSaved->choice_image = $choice['choice_image'] ?? null;
                    $choiceSaved->survey_type = $choice['survey_type'] ?? null;
                    $choiceSaved->save();
                }

                $country = (new CountryService($request))->getCountry();

                $country = !is_null(auth()->user()->country_id) ? auth()->user()->country_id : ($country ? $country->id : 0);

                $vote = new SurveyVote();
                $vote->survey_id = $survey->id ?? request('id');
                $vote->choice_id = $choiceSaved->id;
                $vote->user_id = auth()->id();
                $vote->country_id = $country;

                if (!empty($choice['marking'])) {
                    $vote->mark = $choice['marking'];
                }

                $vote->save();
            }
        }
    }

    /**
     * @return mixed
     */
    private function saveSurvey($request)
    {
        if (request('type') === 'special') {
            // self::homeUpdate($request);
        }

        $is_world = 0;
        $country_id = auth()->user()->country_id;
        if ( request('is_world') || !$country_id ) {
            $is_world = 1;
            $country_id = NULL;
        }

        $createData = [
            'title' => request('title'),
            'description' => request('description'),
            'user_id' => auth()->id(),
            'category_id' => request('category_id'),
            'status' => request('type') == 'normal' ? 0 : request('status', 0),
            'type' => request('type'),
            'start_at' => date('Y-m-d', strtotime((string) request('start_at'))),
            'expire_at' => date('Y-m-d', strtotime((string) request('expire_at'))),
            'show_on_home' => request('show_on_home'),
            'country_id' => $country_id,
            'is_world' => $is_world
        ];
        $survey = Survey::create($createData);
        return $survey;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function statusUpdate(Survey $survey)
    {
        $survey->update([
            'status' => (int)request('status')
        ]);

        $this->jsonResponse->setData(200, 'msg.info.category.confirmation', $survey);
        return $this->jsonResponse->getResponse();
    }

    /**
     * @return array
     */
    public function showOnHomeUpdate(Survey $survey, Request $request)
    {
        //$this->homeUpdate($request);

        $survey->update([
            'show_on_home' => (int)request('show_on_home')
        ]);


        $this->jsonResponse->setData(200, 'msg.info.category.confirmation', $survey);
        return $this->jsonResponse->getResponse();
    }

    public function homeUpdate($request)
    {
        $country = (new CountryService($request))->getCountry();

        $forUpdate = Survey::where('show_on_home', '=', true)
            ->where('country_id', '=', auth()->user()->country_id ?: null)->first();
        if ($forUpdate) {
            $forUpdate->show_on_home = false;
            $forUpdate->save();
        }
    }

    public function hasSurvey($id)
    {
        $survey = Survey::where('category_id', '=', $id)->get();
        $this->jsonResponse->setData(200, 'msg.info.survey.list', $survey);
        return $this->jsonResponse->getResponse();
    }

    public function newest(Request $request)
{
    // Retrieve the country based on the request
    $country = (new CountryService($request))->getCountry();

    // Initialize the query builder
    $query = Survey::withTrashed()
        ->where('created_at', '>=', Carbon::now()->subDays(38)->toDateTimeString())
        ->where('status', true)
        ->whereNotNull('category_id'); // 19-06-25

    if ($country) {
        // If a country is found, filter by country_id
        $query->where('country_id', $country->id);
    } else {
        // If no country is found, filter by is_world
        $query->where('is_world', 1);
    }

    // Execute the query and get the results
    $model = $query->get();

    // Set and return the JSON response
    $this->jsonResponse->setData(200, 'msg.info.survey.list', $model);
    return $this->jsonResponse->getResponse();
}

public function topVoted(Request $request)
{
    // Get the survey votes grouped by survey_id and ordered by the sum of marks
    $votes = SurveyVote::groupBy('survey_id')
        ->selectRaw('survey_id, sum(mark) as sum')
        ->orderBy('sum', 'desc')
        ->get();

    // Extract survey IDs from the votes
    $ids = $votes->pluck('survey_id')->toArray();

    // Retrieve the country based on the request
    $country = (new CountryService($request))->getCountry();

    // Initialize the query builder
    $query = Survey::withTrashed()
        ->whereIn('id', $ids)
        ->where('status', true)
        ->take(CustomHelper::TOP_VOTED_SURVEY_LIMIT)
        ->whereNotNull('category_id'); // 19-06-25

    if ($country) {
        // If a country is found, filter by country_id
        $query->where('country_id', $country->id);
    } else {
        // If no country is found, filter by is_world
        $query->where('is_world', 1);
    }

    // Execute the query and get the results
    $surveys = $query->get();

    // Set and return the JSON response
    $this->jsonResponse->setData(200, 'msg.info.survey.list', $surveys);
    return $this->jsonResponse->getResponse();
}


    public function fake($id)
    {
        $survey = Survey::with('choices')->find($id);

        $countVoteds = SurveyVote::whereHas('users', function ($relation) {
            $relation->where('social_type', '=', 'fake');
        })
            ->where('survey_id', '=', $id)
            ->where('choice_id', '=', \request('choice_id'))->get();

        $usersId = [];
        foreach ($countVoteds as $countVoted) {
            $usersId[] = $countVoted->user_id;
        }

        if ($countVoteds->count() > \request('count')) {
            foreach ($countVoteds as $countVoted) {
                $users[] = $countVoted->user_id;
                $countVoted->delete();
            }
        }

        $users = User::whereNotIn('id', $usersId)
            // ->where('country_id','=', auth()->user()->country_id)
            ->where('social_type', '=', 'fake')
            ->take(\request('count'))
            ->get();

        foreach ($users as $user) {

            $vote = new SurveyVote();
            $vote->survey_id = $survey->id;
            $vote->choice_id = \request('choice_id');
            $vote->user_id = $user->id;
            $vote->country_id = auth()->user()->country_id;
            if ($survey->type === 'normal') {
                $vote->mark = \request('mark');
            }
            $vote->save();
        }

        $this->jsonResponse->setData(200, 'msg.info.survey.success');
        return $this->jsonResponse->getResponse();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function destroy(Survey $survey)
    {
        //delete survey votes
        $survey->votes()->delete();
        //delete survey choices
        $survey->choices()->delete();
        //delete survey comments 16/06/25
        $survey->comments()->delete();
        //delete survey
        $survey->delete();

        $this->jsonResponse->setData(200, 'msg.info.survey.delete');
        return $this->jsonResponse->getResponse();
    }

    public function specialDateRange()
    {
        $all = Survey::select('surveys.start_at', 'surveys.expire_at')->where('country_id', '=', auth()->user()->country_id)->get();
        $this->jsonResponse->setData(200, 'msg.success.dateRage.list', $all);
        return $this->jsonResponse->getResponse();
    }

    /**
     * @param null $msg
     * @return array
     */
    private function surveyError($msg = null)
    {
        $this->jsonResponse->setData(200, 'msg.error.occured:', '', [$msg]);
        return $this->jsonResponse->getResponse();
    }

    public function hitSurvey($category, Request $request)
    {
        $country = (new CountryService($request))->getCountry();
        $votes = SurveyVote::groupBy('survey_id')
            ->selectRaw('survey_id, sum(mark) as sum')
            ->where('country_id', '=', auth()->user()->country_id ?: null)
            ->orderBy('sum', 'desc')
            ->get();

        $category = Category::where('slug', $category)->first();

        if (!$category) {
            $this->jsonResponse->setData(400, 'msg.error.list', []);
            return $this->jsonResponse->getResponse();
        }

        foreach ($votes as $vote) {
            $survey = Survey::with([
                'choices.votes',
                'subjects',
                'comments.comments.user.userDetails',
                'comments.user.userDetails',
                'comments.comments.likes',
                'comments.likes.user'
            ])->find($vote['survey_id']);

            if (is_null($survey)) {
                continue;
            }

            if (!is_null($survey) && $survey->category_id == $category->id) {
                $this->jsonResponse->setData(200, 'msg.success.list', $survey);
                return $this->jsonResponse->getResponse();
            }
        }
        $this->jsonResponse->setData(200, 'msg.category_list_is_empty', []);
        return $this->jsonResponse->getResponse();
    }
    
    public function surveyGraphImage(Request $request) {
        $data = $request->all();

        //get the base-64 from data
        $base64_str = substr((string) $data['base64_image'], strpos((string) $data['base64_image'], ",")+1);
        $name = $data["name"];
        //decode base64 string
        $image = base64_decode($base64_str);
        Storage::disk('public_uploads')->put("survey/{$name}", $image);
        $file = asset("storage/survey/{$name}");
       
        return response()->json($file);
    }
}
