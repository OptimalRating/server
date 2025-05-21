<?php


/**
 * Class CountryService
 * @package App\Service
 * @author Üveys SERVETOĞLU <uveysservetoglu@gmail.com>
 */

namespace App\Service;


use App\Country;
use Illuminate\Http\Request;

class CountryService
{
    /**
     * @var Request
     */
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getCountry()
    {
        $country = null;
        $headerCountry = $this->request->headers->get('country');
        $IP = $this->request->server->get('REMOTE_ADDR');



        $IPService = (new IpService())->getCountryData($IP);
        $countryCode = $headerCountry ?? $IPService->country_code;
        $country = Country::where('code', '=', $countryCode)->first();
        return $country;
    }

}
