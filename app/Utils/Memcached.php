<?php

/**
 * Class RedisService
 * @package App\Service
 * @author Ćveys SERVETOÄ˛LU <uveysservetoglu@gmail.com>
 */

namespace App\Utils;

use Illuminate\Support\Facades\Cache;

class Memcached implements CacheInterface
{

    /**
     * @param $key
     * @param $cache
     * @param int $timeout
     */
    public function set($key, $cache, $timeout = 3600)
    {
        serialize(Cache::put($key, $cache, $timeout));
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function get($key)
    {
        return unserialize(Cache::get($key));
    }
}
