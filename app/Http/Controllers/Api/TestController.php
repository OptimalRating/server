<?php


/**
 * Class TestController
 * @package App\Http\Controllers\Api
 * @author Üveys SERVETOĞLU <uveysservetoglu@gmail.com>
 */

namespace App\Http\Controllers\Api;


use App\Service\MailService;

class TestController
{
    public function test(MailService $service): never
    {
        $body = $service->test();

        dd($body);
    }
}
