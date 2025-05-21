<?php


/**
 * Class UserService
 * @package App\Service
 * @author Üveys SERVETOĞLU <uveysservetoglu@gmail.com>
 */

namespace App\Service;


use App\Category;
use App\Comment;
use App\CommentLike;
use App\Friends;
use App\PendingFriends;
use App\Survey;
use App\User;
use App\UserPrivacySetting;

class UserService
{

    public function deleteUser(User $user)
    {
        $userDetail = $user->userDetails()->first();

        if (!is_null($userDetail)) {
            $userDetail->delete();
        }

        self::deleteSurvey($user);
        self::deleteComments($user);
        self::deleteCategories($user);
        self::deleteFriend($user);
        self::deletePendingFriend($user);
        $user->delete();

    }

    public function deletePrivacySettings($user)
    {
        UserPrivacySetting::where('user_id','=', $user->id)->delete();
    }

    public function deleteFriend($user)
    {
        Friends::where('user','=', $user->id)->delete();
        Friends::where('friend','=', $user->id)->delete();
    }

    public function deletePendingFriend($user)
    {
        PendingFriends::where('user','=', $user->id)->delete();
        PendingFriends::where('friend','=', $user->id)->delete();
    }

    public function deleteCategories($user)
    {
        $categories = Category::where('user_id','=', $user->id)->get();

        foreach ($categories as $category) {

            $category->delete();
        }
    }

    public function deleteComments($user)
    {
        CommentLike::where('user_id','=', $user->id)->delete();
        $comments = Comment::where('user_id','=', $user->id)->get();

        foreach ($comments as $comment) {
            $comment->likes()->delete();
            $comment->delete();
        }
    }

    public function deleteSurvey($user)
    {
        $surveys = Survey::where('user_id','=', $user->id)->get();

        if (!count($surveys)) {
            return;
        }

        foreach ($surveys as $survey) {
            $choices = $survey->choices()->get();

            foreach ($choices as $choice) {
                $choice->votes()->delete();
                $choice->delete();
            }
            $survey->delete();
        }
    }
}
