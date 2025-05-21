<?php

namespace App\Http\Controllers\Api;

use App\Friends;
use App\Http\Controllers\Controller;
use App\PendingFriends;
use App\Service\CustomJsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FriendsController extends Controller
{
    private $response;

    public function __construct(CustomJsonResponse $response)
    {
        $this->response = $response;
    }

    public function listMyFriend()
    {
        $user = auth()->user();
        $myFriend = Friends::where('user', '=', $user->id)
            ->where('status','=','active')
            ->first();

        $this->response->setData(400, 'msg.info.already_friend', $myFriend);
        return $this->response->getResponse();
    }

    public function listRequestFriend()
    {
        $user = auth()->user();
        $myFriend = PendingFriends::where('friend', '=', $user->id)
            ->where('status','=','pending')
            ->first();

        $this->response->setData(400, 'msg.info.already_friend', $myFriend);
        return $this->response->getResponse();
    }

    public function requestFriend($friend)
    {
        $user = auth()->user();

            $myFriend = Friends::where('user', '=', $user->id)
            ->where('friend', '=', $friend)
            ->first();

        if (!is_null($myFriend)) {
            $this->response->setData(400, 'msg.info.already_friend');
            return $this->response->getResponse();
        }

        $myFriend = PendingFriends::where('user', '=', $user->id)
            ->where('friend', '=', $friend)
            ->where('status', '=', 'pending')
            ->first();

        if (!is_null($myFriend)) {
            $this->response->setData(400, 'msg.info.already_friend_request');
            return $this->response->getResponse();
        }

        $req = new PendingFriends();

        $req->user = $user->id;
        $req->friend = $friend;
        $req->status = 'pending';
        $req->save();

        $this->response->setData(null, 'msg.info.friend_request_success');
        return $this->response->getResponse();
    }

    public function requestAnswer($friend, Request $request)
    {
        $answer = request()->json('answer');

        if (is_null($answer)) {
            $this->response->setData(400, 'msg.info.please_answer_friend_request');
            return $this->response->getResponse();
        }

        $user = auth()->user();

        $request = PendingFriends::where('user', '=', $friend)
            ->where('friend', '=', $user->id)
            ->first();

        if (is_null($request)) {
            $this->response->setData(400, 'msg.info.not_friend_request');
            return $this->response->getResponse();
        }

        if ($answer === 'no') {
            $request->status = 'passive';
            $request->save();

            $this->response->setData(400, 'msg.info.friend_request_reject');
            return $this->response->getResponse();
        }

        if ($answer !== 'yes') {
            $this->response->setData(400, 'msg.info.please_a_valid_request');
            return $this->response->getResponse();
        }




        self::addFriend($user->id, $friend);
        self::addFriend($friend, $user->id);

        $request->delete();
        $this->response->setData(200, 'msg.info.answer_request_approved');
        return $this->response->getResponse();

    }

    public function cancelRequestFriend($id)
    {
        $myFriend = PendingFriends::where('user', '=', auth()->id())
            ->where('friend', '=', $id)
            ->where('status', '=', 'pending')
            ->first();

        if (is_null($myFriend)) {
            $this->response->setData(400, 'msg.info.not_request_friend');
            return $this->response->getResponse();
        }

        $myFriend->delete();

        $this->response->setData(200, 'msg.info.deleted_friends');
        return $this->response->getResponse();
    }

    private function addFriend($user, $friend)
    {
        $myFriend = Friends::where('user', '=', $user)
            ->where('friend', '=', $friend)
            ->first();

        if (!is_null($myFriend)) {
            $this->response->setData(400, 'msg.info.already_friend');
            return $this->response->getResponse();
        }

        $model = new Friends();
        $model->user = $user;
        $model->friend = $friend;
        $model->status = 'active';
        $model->save();
    }

    public function deleteFriend()
    {
        $friends = request()->json('friends');

        if (!$friends) {
            $this->response->setData(200, 'msg.info.required');
            return $this->response->getResponse();
        }

        foreach ((array)$friends as $friend) {
            $myFriend = Friends::where('user', '=', auth()->id())
                ->where('friend', '=', $friend)
                ->first();

            if (!is_null($myFriend)) {
                $myFriend->delete();
            }

            $myFriend = Friends::where('friend', '=', auth()->id())
                ->where('user', '=', $friend)
                ->first();

            if (!is_null($myFriend)) {
                $myFriend->delete();
            }
        }
        $this->response->setData(200, 'msg.info.deleted_friends');
        return $this->response->getResponse();
    }
}
