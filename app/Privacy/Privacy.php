<?php


/**
 * Class Privacy
 * @package App\Privacy
 * @author Üveys SERVETOĞLU <uveysservetoglu@gmail.com>
 */

namespace App\Privacy;


use App\Friends;
use App\User;
use App\UserDetail;
use App\UserPrivacySetting;

trait Privacy
{
    public function getPrivacyForGenericAttribute($user, $model, $privacy)
{
    $show = isset($this->attributes[$privacy]) ? $this->attributes[$privacy] : null;
    $role = ['super_admin', 'country_admin', 'user'];

    if (!is_null($model) && !is_null($user) && $user->id == $model->id) {
        return $show;
    }

    if (!empty($user) && $user->hasAnyRole($role)) {
        return $show;
    }

    $privacyModel = \App\Privacy::where('slug', '=', $privacy)->first();

    if (is_null($privacyModel)) {
        return null;
    }

    if ($model instanceof User) {
        $roleInstance = $model->roles()->first();
        if ($roleInstance && in_array($roleInstance->name, $role)) {
            return $show;
        }
    }

    $userPrivacySetting = UserPrivacySetting::with(['option', 'privacy'])
        ->where('privacy_id', '=', $privacyModel->id)
        ->where(
            'user_id',
            '=',
            $model instanceof UserDetail ? $model->user_id : $model->id
        )
        ->first();

    if (is_null($userPrivacySetting)) {
        return null;
    }

    $option = $userPrivacySetting->option()->first();

    if ($option && strtolower($option->option) === 'everyone') {
        return isset($this->attributes[$privacy]) ? $this->attributes[$privacy] : null;
    }

    if (is_null($user) || (strtolower($option->option) === 'nobody')) {
        return null;
    }

    if (strtolower($option->option) === 'friend') {
        $friend = Friends::where('user', '=', $user->id)
            ->where('friend', '=', $model->id)
            ->first();

        return !is_null($friend) ? $show : null;
    }

    return null;
}

    // public function getPrivacyForGenericAttribute($user, $model, $privacy) // old code
    // {

    //     $show = isset($this->attributes[$privacy]) ? $this->attributes[$privacy] : null;
    //     $role = ['super_admin', 'country_admin', 'user'];

    //     if(!is_null($model) && !is_null($user) && $user->id == $model->id) {
    //         return $show;
    //     }

    //     if (!empty($user) && $user->hasAnyRole($role)) {
    //         return $show;
    //     }

    //     $privacyModel = \App\Privacy::where('slug','=',$privacy)->first();

    //     if(is_null($privacyModel)) {
    //         return null;
    //     }

    //     if ($model instanceof User && in_array($model->roles()->first()->name, $role)) {
    //         return $show;
    //     }

    //     $userPrivacySetting = UserPrivacySetting::with(['option','privacy'])
    //         ->where('privacy_id','=', $privacyModel->id)
    //         ->where('user_id','=',
    //             $model instanceof UserDetail ? $model->user_id : $model->id)
    //         ->first();

    //     if (is_null($userPrivacySetting)) {
    //         return null;
    //     }

    //     $option = $userPrivacySetting->option()->first();

    //     if (strtolower($option->option) === 'everyone') {
    //         return isset($this->attributes[$privacy]) ? $this->attributes[$privacy] : null;
    //     }

    //     if(strtolower($option->option) === 'nobody' || is_null($user)) {
    //         return null;
    //     }

    //     if(strtolower($option->option) === 'friend') {

    //         $friend = Friends::where('user','=',$user->id)->where('friend','=',$model->id)->first();

    //         return !is_null($friend) ? $show :  null;
    //     }
    // }
}
