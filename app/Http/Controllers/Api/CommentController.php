<?php

namespace App\Http\Controllers\Api;

use App\Survey;
use App\Comment;
use App\Category;
use App\Helper\CustomHelper;
use Illuminate\Http\Request;
use App\Service\CustomJsonResponse;
use App\CustomObjects\ApiPagination;
use App\Http\Controllers\Controller;
use App\Validator\CategoryValidator;

class CommentController extends Controller
{
    public function __construct(private readonly CustomJsonResponse $jsonResponse)
    {
    }

    /**
     * @return array
     */
    public function comments()
    {
        if(auth()->user()->hasRole('super_admin')){
            $model = Comment::where('country_id', null)->with(['country','commentable','user']);
        }
        else{
            $model = Comment::where('country_id', auth()->user()->country_id)->with(['country','commentable','user']);
        }

        $pagination = new ApiPagination(request("limit", 20), is_countable($model->get()) ? count($model->get()) : 0, request("offset", 0));

        $this->jsonResponse->setData(200, 'msg.info.list.comments', $model->get(), null, null);

        return $this->jsonResponse->getResponse();
    }


    public function store( Request $request )
    {
        //is authorized(Login).
        //is user is commenting on specific world other then registered
        //is is_world = true.

        if ( CustomHelper::isUserAuthorized() ) {
            return $this->jsonResponse->setData(400, '', '','msg.err.user-not-authorized')->getResponse();
        }

        $country_id = NULL;
        $is_world = 0;
        if(request()->json('survey_id')) {
            $model = Survey::find(request()->json('survey_id'));
            $country_id = $model->country_id;
        } else {
            $model = Comment::find(request()->json('comment_id'));
        }

        // Is header -> country is NULL (is_world = true) set country_id = 0
        $headerCountry = $request->header('country');
        if ( $headerCountry == 'null' ) {
            $country_id = NULL;
            $is_world = 1;
        }


        // is not Survey = is_world
        if( $headerCountry != 'null' ) {
            $user = auth()->user();
            if($model->country_id != $user->country_id) {
                // return $this->jsonResponse->setData(400, 'msg.info.survey.comment.created', '','msg.not_allowed')->getResponse();
                return $this->jsonResponse->setData(400,'msg.error_unauthorized_country')->getResponse();
            }
        }

        $comment = $model->comments()->create([
            'body' => request()->json('body'),
            'user_id' => auth()->id(),
            'country_id' => $country_id,
            'is_world' => $is_world,
        ]);

        if($comment){
            $this->jsonResponse->setData(200,  'msg.info.survey.comment.created', $comment);
            return $this->jsonResponse->getResponse();
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @return array|bool
     */
    public function update(Request $request, Comment $comment)
    {
        $reqAll = $request->json()->all();

        $comment->update($reqAll);

        $this->jsonResponse->setData(200,  'msg.info.success.comment.update', $comment);
        return $this->jsonResponse->getResponse();
    }

    public function destroy(Request $request, Comment $comment)
    {
        $comment->delete();

        return $this->jsonResponse->setData(200,   'msg.info.success.comment.delete')->getResponse();
    }

    public function getSurveyComments(Survey $survey)
    {
        $model  = self::prepareTree($survey->comments);

        $pagination = new ApiPagination(request("limit", 20), is_countable($model) ? count($model) : 0, request("offset", 0));

        $this->jsonResponse->setData(
            200,
            'msg.info.list.comments', $model, null, $pagination->getConvertObject()
        );

        return $this->jsonResponse->getResponse();
    }

    public function prepareTree($comments)
    {
        $allComments = [];
        foreach ($comments as $key => $comment){
            self::prepareTree($comment->comments);
            $allComments[] = $comment;
        }

        return $allComments;
    }


    public function changeStatus(Comment $comment)
    {
        $model = $comment->update([
            'status' => request()->json('status')
        ]);

        $this->jsonResponse->setData(200, 'msg.info.list.comment', $model);

        return $this->jsonResponse->getResponse();
    }

}
