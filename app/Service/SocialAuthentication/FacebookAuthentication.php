<?php

/**
 * Class FacebookAuthentication
 * @package App\Service\SocialAuthentication
 * @author Üveys SERVETOĞLU <uveysservetoglu@gmail.com>
 */

namespace App\Service\SocialAuthentication;

use GuzzleHttp\Exception\GuzzleException;

class FacebookAuthentication extends SocialAuthentication
{
    public function verify()
    {
        $url = 'https://graph.facebook.com/me?fields=email,name&access_token='.request('token');

        try {
            $getService = $this->client->get($url);
        } catch (GuzzleException $exception) {
            return false;
        }

        $body = json_decode($getService->getBody()->getContents());

        if ($body && !isset($body->id)) {
            return false;
        }


        return [
            'uid' => $body->id,
            'email' => $body->email
        ];
    }
}
