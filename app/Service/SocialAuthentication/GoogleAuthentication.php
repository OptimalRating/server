<?php

/**
 * Class FacebookAuthentication
 * @package App\Service\SocialAuthentication
 * @author Üveys SERVETOĞLU <uveysservetoglu@gmail.com>
 */

namespace App\Service\SocialAuthentication;

use GuzzleHttp\Exception\GuzzleException;

class GoogleAuthentication extends SocialAuthentication
{
    public function verify()
    {
        $serviceUrl = 'https://oauth2.googleapis.com/';
        $url = $serviceUrl.'tokeninfo?id_token='.request('token');

        try {
            $getService = $this->client->get($url);
        } catch (GuzzleException) {
            return false;
        }

        $body = json_decode((string) $getService->getBody()->getContents(), null, 512, JSON_THROW_ON_ERROR);

        return [
            'uid' => $body->sub,
            'email' => $body->email
        ];
    }
}
