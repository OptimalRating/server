<?php


namespace App\Service;

use App\Keyword;
use App\Translation;
use GuzzleHttp\Client;

class SmsService
{

    private readonly string $serviceUrl;
    private readonly \GuzzleHttp\Client $client;

    /**
     * SmsService constructor.
     */
    public function __construct()
    {
        $this->client     = new Client();
        $this->serviceUrl = "https://pm8q8.api.infobip.com/sms/2/text/advanced";
        $this->apiKey     = base64_encode('uniter:Mrtdnlr1!');
    }

    public function sendSmsVerify($phone)
    {
        $verifyCode = random_int(10000, 99999);
        $smsContent = "Optimal Rating ".$verifyCode.PHP_EOL;

        $req = self::send($phone, $smsContent);

        $smsResponse = json_decode((string) $req->getBody()->getContents(), null, 512, JSON_THROW_ON_ERROR);

        if($smsResponse->messages[0]->status->groupId !== 1)
            return ['status' => false];

        return ['status' => true, 'code'   => $verifyCode];
    }

    public function categoryConfirmMessage($category)
    {
        $user = $category->user()->with('country')->first();

        $country = $user->country()->get();

        $keyword = Keyword::where('key','=','msg.info.category.confirmation')->first();

        $message = 'Your category has been approved';

        if ($keyword) {

            $translation = Translation::where('keyword_id', '=', $keyword->id)
                ->where('country_code','=', $country->code)->first();

            $message = $translation->translation;
        }

        $userDetail = $user->userDetails()->first();

        self::send($userDetail->phone_number, $message);
    }

    private function send($phone, $smsContent)
    {
        return $this->client->request('POST', $this->serviceUrl,

            ['json' => ['messages' => [['from'  => '08505400794', 'destinations' => [['to'    => $phone]], 'text' => $smsContent]]], 'headers' => ['accept' => 'application/json', 'Content-Type' => 'application/json', 'authorization' => 'Basic '.$this->apiKey]]
        );
    }
}

//$this->serviceUrl = "https://sms.par-ken.com/api/smsapi?key=7ec2fec93c9f8a1231fa3caa6884803d&route=4&sender=ALERTS&number=$userDetail->phone_number&sms=Optimal Rating ".$verifyCode.PHP_EOL&templateid=12011628764110XXXXX
//";