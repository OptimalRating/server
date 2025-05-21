<?php

namespace App\Helper;

class CustomHelper
{
    public const TOP_VOTED_SURVEY_LIMIT = 5;

    public static function isUserAuthorized() {
        $user = auth()->user();
        return $user->status == 'approved' && $user->phone_verify == 1;
    }
}
