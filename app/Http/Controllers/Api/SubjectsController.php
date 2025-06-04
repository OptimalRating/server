<?php

namespace App\Http\Controllers\Api;

use App\Survey;
use App\Country;
use App\Keyword;
use App\Subject;
use App\SurveyVote;
use App\SurveyChoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Service\KeywordService;
use Illuminate\Support\Facades\DB;
use App\Service\CustomJsonResponse;
use App\Validator\SubjectValidator;
use App\CustomObjects\ApiPagination;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubjectsController extends Controller
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
     * Display a listing of the resource.
     *
     * @return array
     */
    public function index()
    {
        $model = Subject::all();

        $pagination = new ApiPagination(request("limit", 20), count($model), request("offset", 0));

        $this->jsonResponse->setData(
            200,
            'msg.info.list.subjects', $model, null, $pagination->getConvertObject()
        );

        return $this->jsonResponse->getResponse();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return array
     */
    public function store(Request $request)
    {
        $validator = new SubjectValidator();

        if($response = $validator->validate())
            return $response;

        $subject = new Subject();
        $subject->title = $request->json('title');
        // $subject->translate_key = 'subject.'.str_slug($subject->title);
        $subject->translate_key = 'subject.'. Str::slug($subject->title);
        // $keyword = new KeywordService($subject->translate_key, $subject->title, 'subject');
        $subject->save();
        if($subject){
            Keyword::create([
                'key' => $subject->translate_key,
                'default' => $subject->title
            ]);
            $this->jsonResponse->setData(200,  'msg.info.subject.created', $subject);
            return $this->jsonResponse->getResponse();
        }

    }

    /**
     * Display the specified resource.
     *
     * @param Subject $subject
     * @return array
     */
    public function show(Subject $subject)
    {
        $this->jsonResponse->setData(200,   'msg.info.success.subject.show', $subject);
        return $this->jsonResponse->getResponse();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Subject $subject
     * @return array
     */
    public function update(Request $request, Subject $subject)
    {
        //update the subject title if needed
        $subject->title = $request->json('title');
        $subject->translate_key = 'subject.'.str_slug($subject->title);

        $updated = $subject->update();

        $keyword = new KeywordService($subject->translate_key, $subject->title, 'subject');

        if ($updated) {
            $this->jsonResponse->setData(200,  'msg.subject.updated', $subject);
            return $this->jsonResponse->getResponse();
        }
    }

    // public function subjectHasSurvey( Request $request, $id)
    // {
    //     $now = (new Carbon());
    //     $headerCountry = $request->header('country');
    //     $is_world = 1;
    //     $country_id = NULL;
    //     if ( $headerCountry != 'null' ) {
    //         $country = Country::where('code', $headerCountry)->first();
    //         $country_id = $country->id;
    //         $is_world = 0;
    //     }

    //     $subject = Subject::where('id','=', $id)->first();
        
    //     $totalCount = $subject->surveys()->where([
    //         'is_world' => $is_world,
    //         'country_id' => $country_id,
    //     ])->groupBy('surveys.id')->count();

    //     $surveys = $subject->surveys()->where([
    //         'is_world' => $is_world,
    //         'country_id' => $country_id,
    //     ])
    //     // ->where('expire_at', '>', $now->toDateTimeString())
    //     // ->where('start_at', '<', $now->toDateTimeString())
    //     ->limit(request('take', 2))->offset(request('offset', 0))->groupBy('surveys.id');


    //     if ( $request->orderBy == 'date' ) {
    //         $surveys->orderBy('created_at', 'desc');
    //     }
    //     $subject->setRelation('surveys', $surveys->get());

    //     // return response()->json($subjectsurveys);
    //     foreach ( $subject->surveys as $key => $survey ) {
    //         $vote = SurveyVote::groupBy('survey_id')
    //                 ->selectRaw('sum(mark) as total_votes')
    //                 ->where('survey_id', $survey->id);
    //         if ( $request->orderBy == 'vote' ) {
    //             $vote->orderBy('total_votes', 'desc');
    //         }
    //         $vote = $vote->first();

    //         $survey->survey_votes = $vote->total_votes;
    //     }
        
    //     // $count = count($subject->surveys);
    //     $pagination = new ApiPagination(request("limit", 2), $totalCount, request("offset", 0));
        
    //     $this->jsonResponse->setData(200, 'msg.subject.list', $subject, null, $pagination->getConvertObject());
    //     return $this->jsonResponse->getResponse();
    // }


    public function subjectHasSurvey(Request $request, $slug)
    {
        $now = new Carbon();
        $headerCountry = $request->header('country');
        // \Log::info('Country Code from Header:', ['country' => $headerCountry]);
        // \Log::info('Subject Slug:', ['slug' => $slug]);
        $is_world = 1;
        $country_id = null;
    
        // Handle "world" context
        if ($headerCountry === 'world') {
            \Log::info('Request is for global surveys ("world" context)');
        } else {
            // Validate Country Header
            if (!$headerCountry || $headerCountry === 'null') {
                return response()->json([
                    'message' => 'Country header is missing or invalid.',
                    'status' => 400,
                ], 400);
            }
    
            // Find country by code
            $country = Country::where('code', $headerCountry)->first();
            if ($country) {
                $country_id = $country->id;
                $is_world = 0; // Not "world"
            } else {
                return response()->json([
                    'message' => 'Country not found.',
                    'status' => 404,
                ], 404);
            }
        }
    
        // Fetch the subject using slug
        $subject = Subject::where('slug', $slug)->first();
        if (!$subject) {
            return response()->json([
                'message' => 'Subject not found.',
                'status' => 404,
            ], 404);
        }
    
        // Fetch total count of surveys for the subject
        $totalCount = $subject->surveys()->where(function ($query) use ($is_world, $country_id) {
                    $query->where('is_world', $is_world);
                    if (!$is_world) {
                        $query->where('country_id', $country_id);
                    }
                })->count(); // Total count of surveys

                $take = $request->get('take', 5); // Default to 5 items per page
                $offset = $request->get('offset', 0);
        // Fetch surveys with the necessary filters
        $surveys = $subject->surveys()->with([
            'choices.votesSpecial',
            'subjects', // Ensure this relationship is correctly loaded
            'comments' => function ($query) {
                $query->withTrashed()->with([
                    'comments.user.userDetails', 
                    'user.userDetails', 
                    'likes.user'
                ]);
            },
            'user.userDetails'
        ])->where([
            'is_world' => $is_world,
            'country_id' => $country_id,
        ])
            ->limit($take)
            ->offset($offset)
            ->groupBy('surveys.id');
    
        if ($request->orderBy == 'date') {
            $surveys->orderBy('created_at', 'desc');
        }
    
        // Set relation for surveys
        $subject->setRelation('surveys', $surveys->get());
    
        // Add vote count to surveys
        foreach ($subject->surveys as $key => $survey) {
            $vote = SurveyVote::groupBy('survey_id')
                ->selectRaw('sum(mark) as total_votes')
                ->where('survey_id', $survey->id);
    
            if ($request->orderBy == 'vote') {
                $vote->orderBy('total_votes', 'desc');
            }
    
            $vote = $vote->first();
            $survey->survey_votes = $vote->total_votes ?? 0; // Default to 0 if no votes
        }
    
        // Create pagination
        $pagination = new ApiPagination($take, $totalCount, $offset);

        $this->jsonResponse->setData(200, 'msg.subject.list', $subject, null, $pagination->getConvertObject());
        return $this->jsonResponse->getResponse();
    }
    
    
    /**
     * Remove the specified resource from storage.
     *
     * @param Subject $subject
     * @return array
     * @throws \Exception
     */
    public function destroy(Subject $subject)
    {
        $keyword = Keyword::where('key', '=', $subject->translate_key);
        $keyword->delete();
        $deleted = $subject->delete();

        if($deleted){
            $this->jsonResponse->setData(200,  'msg.subject.deleted', $subject);
            return $this->jsonResponse->getResponse();
        }

    }
}
