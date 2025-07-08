<?php

namespace App\Http\Controllers\Api;

use App\Country;
use App\CustomObjects\CustomJsonResponse;
use App\Friends;
use App\Http\Controllers\Controller;
use App\Keyword;
use App\KeywordsCache;
use App\Page;
use App\Privacy;
use App\Service\CacheService;
use App\Service\IpService;
use App\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class InitController extends Controller
{

    /**
     * @return array
     */
    public function init(Request $request, CacheService $cache)
    {
        // Log::info('INIT reqest latest',[$request]);
        $IP = $request->server->get('REMOTE_ADDR');

        $IPService = $cache->cache->get($IP);

        if (is_null($IPService) || !$IPService) {
            $IPService = (new IpService())->getCountryData($IP);
            $cache->cache->set($IP, serialize($IPService));
        }
        $IPService = (new IpService())->getCountryData($IP);
        
        $init = [
            'ipService' => $IPService,
            'user' => User::with(['userDetails', 'friends.friend.userDetails', 'pendingFriends', 'pendingFriends.user.userDetails', 'country', 'pendingRequestFriends', 'city'])->find(Auth::id())
        ];

        $customResponse = new CustomJsonResponse(
            200,
            'msg.info.init.success',
            $init
        );
        // Log::info('INIT ',[$init]);
        return $customResponse->getResponse();
    }


    /**
     * @return array
     */
    public function panelInit()
    {
        $auth = Auth::user();
        $country = Country::find($auth->country_id);
        $friend = Friends::with(['friend.userDetails'])->where('user', '=', $auth->id)->get();

        $init = ['country' => $country, 'role' => $auth->roles[0]];
        $customResponse = new CustomJsonResponse(
            200,

            'msg.info.login.success',
            $init
        );

        return $customResponse->getResponse();
    }

    public function i18n()
    {

        $keyword = KeywordsCache::orderBy('id', 'desc')->first();

        if ($keyword == null) {

            (new CacheService())->keywordCacheCreate();
            $keyword = KeywordsCache::orderBy('id', 'desc')->first();
        }
         // Log the decoded body
        Log::info('i18n response body:', ['body' => $decodedBody]);
        return response()->json(json_decode((string) $keyword->body, null, 512, JSON_THROW_ON_ERROR));
    }
}
