<?php


namespace App\Service;

use GuzzleHttp\Client;

class LanguagesService
{

    private $client;
    private $countries;
    private $ipData;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiUrl;

    /**
     * SmsService constructor.
     * @param $countries
     * @param $ipData
     */
    public function __construct($countries, $ipData)
    {
        $this->client     = new Client();
        $this->countries = $countries;
        $this->ipData = $ipData;
        $this->apiKey = 'trnsl.1.1.20191117T111038Z.cd0f674744adf370.c74fbe46e4d993b6fc305dc18b4936748b85ec0e';
        $this->apiUrl = 'https://translate.yandex.net/api/v1.5/tr.json/translate?';
    }

    public function languagesCountries()
    {
        foreach ($this->countries as $country){
            try{
                $country->locale_lang = self::translate($country->name_en, 'en', $this->ipData->language_code);
            }catch (\Exception $exception){
                $country->locale_lang = self::translate($country->name_en, 'en');
            }
        }

        return $this->countries;
    }

    public function translate($text, $textLang ='en', $tLang = 'en')
    {

        $url = $this->apiUrl . 'lang='. $textLang . '-' . $tLang . '&key=' . $this->apiKey . '&text=' . $text;

        $client = $this->client->get($url);

        $json = json_decode($client->getBody()->getContents(), true);

        return $json['text'][0];
    }
}
