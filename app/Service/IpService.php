<?php


/**
 * Class IpService
 * @package App\Service
 * @author Üveys SERVETOĞLU <uveysservetoglu@gmail.com>
 */

namespace App\Service;

use App\IpCache;
use App\Utils\CountryCode;
use GuzzleHttp\Client;
use League\Flysystem\File;

class IpService
{
    public function getCountryData($IP)
    {
        //https://api.ipdata.co/45.135.206.200?api-key=9b8a49e10bc989a900a2d1052999045d9450c3cee479de571ac81d9e

        $apiResponse = (json_decode(self::getCacheData($IP)));
        if (is_null($apiResponse)) {
            $apiResponse = $this->getData($IP);
            self::setCache($IP, $apiResponse);
        }else{
            $apiResponse = json_decode($apiResponse->body);
        }

        return $apiResponse;
    }

    public function getData($IP)
    {
        $IP = self::ipCheck($IP);

        try{
            $url= "http://www.geoplugin.net/json.gp?ip=".self::ipCheck($IP);
            $apiResponse = self::getIpData($url, "returnObjectForGeoPlugin");
        }catch (\Exception $exception) {
            $url= "https://api.ipdata.co/".$IP.'?api-key=a685452584680f0c96918c6e608d547601dc62943de337c63f425cc2';
            $apiResponse = self::getIpData($url, "returnObjectForApiData");
        }

        return $apiResponse;
    }

    public function getIpData($url, $function)
    {
        $client    = new Client();
        $req = json_decode($client->request('GET', $url)->getBody()->getContents());
        return self::{$function}($req);
    }

    private function setCache($key, $body)
    {
        $cache = new IpCache();
        $cache->ip = $key;
        $cache->body = json_encode($body);
        $cache->save();
    }
    private function getCacheData($key)
    {
        $data = IpCache::where('ip','=', $key)->first();


        return is_null($data)  ? null : $data;
    }
    private function ipCheck($IP)
    {
        switch ($IP){
            case '127.0.0.1':
            case 'localhost':
            case '::1':
                return '88.252.31.244';
            default :
                return $IP;
        }
    }

    private function returnObjectForApiData($object)
    {
        //file_put_contents('cdn/images/country/'.strtolower($object->country_code).'.png', file_get_contents($object->flag));
        $newObject = new \stdClass();

        $countries = CountryCode::COUNTRY;
        $langCode = explode(',', $countries[strtolower($object->country_code ?? $object->continent_code)]);

        $newObject->country_code = $object->country_code;
        $newObject->language_code = $langCode[0];
        $newObject->country = $object->country_name;
        $newObject->city = $object->city;
        $newObject->region_code = $object->region_code;
        $newObject->flag = $object->country_code.'.png';
        $newObject->ip = $object->ip;

        return $newObject;
    }

    private function returnObjectForGeoPlugin($object)
    {
        //file_put_contents('cdn/images/country/'.strtolower($object->country_code).'.png', file_get_contents($object->flag));
        $newObject = new \stdClass();

        $countries = CountryCode::COUNTRY;
        $langCode = explode(',', $countries[strtolower($object->geoplugin_countryCode ?? $object->geoplugin_continentCode)]);

        $newObject->country_code = $object->geoplugin_countryCode;
        $newObject->language_code = $langCode[0];
        $newObject->country = $object->geoplugin_city;
        $newObject->city = $object->geoplugin_city;
        $newObject->region_code = $object->geoplugin_region;
        $newObject->flag = $object->geoplugin_countryCode.'.png';
        $newObject->ip = $object->geoplugin_request;

        return $newObject;
    }
}
