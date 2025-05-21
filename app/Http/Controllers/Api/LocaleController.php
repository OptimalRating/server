<?php

namespace App\Http\Controllers\Api;

use App\Service\CustomJsonResponse;
use App\Service\IpService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LocaleController extends Controller
{
    public function getLocale(Request $request, CustomJsonResponse $customJsonResponse)
    {
        if(auth()->user()){
            $countryObj =  auth()->user()->country;
        }else{
            $ip = $request->server->get('REMOTE_ADDR');
            $ipService = new IpService();
            $countryObj = $ipService->getCountryData($ip);
        }

        return $customJsonResponse->setData(200, 'msg.info.locale', $countryObj )->getResponse();
    }
}
