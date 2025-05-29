<?php


/**
 * Class MailService
 * @package App\Service
 * @author Üveys SERVETOĞLU <uveysservetoglu@gmail.com>
 */

namespace App\Service;

use App\Country;
use App\Keyword;
use App\Translation;
use Illuminate\Support\Facades\Mail;

class MailService
{
    public function sendMail($content, $user, $url = null, $keyword = 'delete_profile')
    {
        $country = Country::where("id", $user->country_id)->first();

        $key = 'lbl.mail_title_'.$keyword;
        $keyword = Keyword::where('key',$key)->first();
        $keywordClick = Keyword::where('key','lbl.mail_click_here')->first();


        if (is_null($keyword)) {
            $keyword = new Keyword();
            $keyword->key = $key;
            $keyword->default = $key;
            $keyword->save();
        }

        if (is_null($keywordClick)) {
            $keyword = new Keyword();
            $keyword->key = 'lbl.mail_click_here';
            $keyword->default = 'Click Here';
            $keyword->save();
        }

        $translation = Translation::where('country_code',$country->code)
        ->where('keyword_id', $keyword->id)->first();

        $title = $keyword->default;
        if ($translation) {
            $title = $translation->translation;
        }

        $translationClick = Translation::where('country_code',$country->code)
            ->where('keyword_id', $keywordClick->id)->first();

        $clickHere = $keywordClick["default"];
        if ($translationClick) {
            $clickHere = $translationClick->translation;
        }

        $body = ["messages" => $content, "click" => $clickHere, "title" => $title, "url" => $url];
        
        Mail::send("send_mail", $body, function ($message) use ($user, $title){
            $message->to($user['email'], "SSS")->subject($title);
        });
        
        return $body;
    }
    public function test()
    {
        $body = ["messages"=> "Merhaba"];

        Mail::send("test", $body, function ($message) {
            $message->to("uveysservetoglu@gmail.com", "SSS")->subject("Delete Profile");
        });

        return $body;
    }
}
