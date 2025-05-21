<?php

namespace App\Http\Controllers\Api;

use App\Comment;
use App\Country;
use App\CommentLike;
use App\Helper\CustomHelper;
use Illuminate\Http\Request;
use App\Service\CustomJsonResponse;
use App\Http\Controllers\Controller;
use App\Repositories\CommentLikeRepository;

class CommentLikeController extends Controller
{
    public function store(Request $request, CustomJsonResponse $jsonResponse)
    {
        $comment = Comment::where('id', $request->comment_id)->first();
        if ( $comment ) {
            if ( CustomHelper::isUserAuthorized() ) {
                return $jsonResponse->setData(400, '', '','msg.err.user-not-authorized')->getResponse();
            }

            $headerCountry = $request->header('country');
            $curentCountry = Country::select(['id', 'code'])->where('code', $headerCountry)->first();
            
            // If logged user country is different
            if ( $headerCountry != 'null' ) {
                if( auth()->user()->country_id != $curentCountry->id ) {

                    $jsonResponse->setData(400,'msg.error_unauthorized_country');

                   return $jsonResponse->getResponse();

                }
            }

            $repository = new CommentLikeRepository();
                
            $this->validate($request, [
                'comment_id' => 'required'
            ]);

            // Store or Delete the commentLike
            $commentLike = $repository->save($request);            
    
            // get comment new likes count
            $likeCount = $repository->getNumberOfLike($request);
            $data = [
                'likeCount' => $likeCount,
            ];

            $msg = 'msg.error_unauthorized_country';

            if ( !empty( $commentLike ) ) {
                $msg = $commentLike['action'] == 'create' ? 'msg.info.like.created' : 'msg.info.like.deleted';
            }
    
            $response = $jsonResponse->setData(200, $msg, $data);
    
            return $response->getResponse();
        } else {
            $response = $jsonResponse->setData(400, 'msg.err.comment.not_found', []);
            return $response->getResponse();
        }
    }
}
