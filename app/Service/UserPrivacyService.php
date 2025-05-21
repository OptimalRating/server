<?php


/**
 * Class UserPrivacyService
 * @package App\Service
 * @author Üveys SERVETOĞLU <uveysservetoglu@gmail.com>
 */

namespace App\Service;


use App\Privacy;
use App\UserPrivacySetting;

class UserPrivacyService
{
    public function userPrivacyCreate($user, $list = [])
    {
        $privacies = Privacy::all();

        if (count($list)) {
            $privacies = Privacy::whereIn('id', $list)->get();
        }

        foreach ($privacies as $privacy) {
            $setting = new UserPrivacySetting();
            $setting->user_id = $user;
            $setting->privacy_id = $privacy->id;
            $setting->option_id = $privacy->default_option;

            $setting->save();
        }
    }
}
