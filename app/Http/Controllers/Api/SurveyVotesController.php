<?php

namespace App\Http\Controllers\Api;

use App\Survey;
use App\SurveyVote;
use App\SurveyChoice;
use App\Helper\CustomHelper;
use Illuminate\Http\Request;
use App\Service\CustomJsonResponse;
use App\Http\Controllers\Controller;

class SurveyVotesController extends Controller
{
    public function __construct(private readonly CustomJsonResponse $customJsonResponse)
    {
    }

    /**
     * @return array
     */
    public function vote(Survey $survey)
    {
        if ( CustomHelper::isUserAuthorized() ) {
            return $this->jsonResponse->setData(400, '', '','msg.must_approved')->getResponse();
        }
        //validate survey vote
        //save survey vote
        if(!$survey->is_world){
            $user = auth()->user();
            if($survey->country_id != $user->country_id){
                return $this->customJsonResponse->setData(400, 'msg.info.not_allowed')->getResponse();
            }
        }

        $choice =  SurveyChoice::find(request()->json('choice_id'));


        $choice->votes()->create([
            'user_id' => auth()->id()
        ]);

        return $this->customJsonResponse->setData(200, 'msg.info.survey_vote.created', $survey)->getResponse();
    }

    /**
     * @param $survey
     * @return array
     * @throws \Exception
     */
    public function submitVote(Survey $survey, Request $request)
    {
        if ( CustomHelper::isUserAuthorized() ) {
            return $this->jsonResponse->setData(400, '', '','msg.must_approved')->getResponse();
        }

        if(!$survey->is_world){
            $user = auth()->user();
            $countryContry = $request->header('country');
            if ( $countryContry != null && $countryContry != 'null' ) {
                if($survey->country_id != $user->country_id) {
                    return $this->customJsonResponse->setData(400, 'msg.info.country_vote_notallowed')->getResponse();
                }
            }
        }

        $choice = self::surveyVote($survey->id, \request('choice_id'));

        if (!is_null($choice)) {
            $choice->delete();
        }

        $user = \auth()->user();
        if ($user->status !== 'approved') {
            return $this->customJsonResponse->setData(400,'msg.info.not_approved')->getResponse();
        }

        $vote = new SurveyVote();
        $vote->survey_id = $survey->id;
        $vote->user_id = auth()->id();

        $vote->choice_id = \request('choice_id');
        $vote->mark = \request('mark', null);

        $vote->save();

        return $this->customJsonResponse->setData(200, 'msg.info.survey_vote.created', $vote)->getResponse();
    }

    /**
     * @param $survey
     * @param $choiceId
     * @return array
     */
    public function checkVote($survey, $choiceId, CustomJsonResponse $jsonResponse)
    {
        $choice = self::surveyVote($survey, $choiceId);

        if (!is_null($choice)) {
            $jsonResponse->setData(200,'msg.info.already_vote', $choice);
            return $jsonResponse->getResponse();
        }

        return $this->customJsonResponse->setData(400,'msg.info.not_found')->getResponse();
    }

    /**
     * @param $survey
     * @param $choiceId
     * @return array
     * @throws \Exception
     */
    public function cancelVote($survey, $choiceId, CustomJsonResponse $jsonResponse)
    {
        $choice = self::surveyVote($survey, $choiceId);

        if (!is_null($choice)) {
            $choice->delete();
            $jsonResponse->setData(200,'msg.info.already_vote', $choice);
            return $jsonResponse->getResponse();
        }

        return $this->customJsonResponse->setData(400,'msg.info.not_found')->getResponse();
    }

    /**
     * @param $survey
     * @param $choice
     * @return SurveyVote|\Illuminate\Database\Eloquent\Builder
     */
    private function surveyVote($survey, $choice)
    {
        $choice = SurveyVote::with('choice')
            ->where('choice_id','=',$choice)
            ->where('survey_id','=',$survey)
            ->where('user_id','=', auth()->id());
        return $choice->first();
    }
}
