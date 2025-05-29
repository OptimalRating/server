<?php


namespace App\Service;

use GuzzleHttp\Client;

class LanguagesService
{

    private readonly \GuzzleHttp\Client $client;

    private readonly string $apiKey;

    private readonly string $apiUrl;

    /**
     * SmsService constructor.
     * @param $countries
     * @param $ipData
     */
    public function __construct(private $countries, private $ipData)
    {
        $this->client     = new Client();
        $this->apiKey = 'trnsl.1.1.20191117T111038Z.cd0f674744adf370.c74fbe46e4d993b6fc305dc18b4936748b85ec0e';
        $this->apiUrl = 'https://translate.yandex.net/api/v1.5/tr.json/translate?';
    }

    public function languagesCountries()
    {
        foreach ($this->countries as $country){
            try{
                $country->locale_lang = self::translate($country->name_en, 'en', $this->ipData->language_code);
            }catch (\Exception){
                $country->locale_lang = self::translate($country->name_en, 'en');
            }
        }

        return $this->countries;
    }

    public function translate($text, $textLang ='en', $tLang = 'en')
    {

        $url = $this->apiUrl . 'lang='. $textLang . '-' . $tLang . '&key=' . $this->apiKey . '&text=' . $text;

        $client = $this->client->get($url);

        $json = json_decode($client->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $json['text'][0];
    }
}
