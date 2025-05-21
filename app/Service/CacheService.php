<?php


/**
 * Class CacheService
 * @package App\Service
 * @author Üveys SERVETOĞLU <uveysservetoglu@gmail.com>
 */

namespace App\Service;

use App\Country;
use App\Keyword;
use App\KeywordsCache;
use App\Page;
use App\Utils\CacheInterface;
use App\Utils\Memcached;
use App\Utils\Redis;


class CacheService
{
    public $cache;

    /**
     * @param Redis $redis
     * @return CacheInterface
     */
    public function __construct()
    {
        switch (config('cache.default'))
        {
            case 'file':
            case 'memcached':
                $this->cache = new Memcached();
                break;
            case 'redis':
                $this->cache = new Redis();
        }

    }

    public function keywordCacheCreate()
    {
        $countryModel = Country::all();
        $response = array();

        foreach ($countryModel as $country)
        {

            $model = Keyword::with(
                [
                    'translations' => function ($q) use ($country)
                    {
                        $q->where('country_code', '=', $country->code);
                    }
                ])->get();
            $obj = new \stdClass();
            foreach ($model as $keyword)
            {
                $obj->{$keyword->key} = count($keyword->translations) ?
                    $keyword->translations[count($keyword->translations) - 1]->translation : $keyword->default;
            }
            /**
             * @todo page lang
             */

            $pages = Page::with('translation')->get();
            foreach ($pages as $page)
            {

                $translation = $page->translation($country->code)->first();

                $obj->{$page->translate_key} =
                    !is_null($translation) ? $translation->title : $page->title;
            }

            /**
             * @todo page lang
             */
            $response[$country->code] = $obj;
        }

        $keywordCache = new KeywordsCache();

        $keywordCache->body = json_encode($response);

        $keywordCache->save();
    }
}
