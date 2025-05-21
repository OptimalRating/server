<?php
/**
 * Class CacheInterface
 * @package App\Utils
 * @author Üveys SERVETOĞLU <uveysservetoglu@gmail.com>
 */

namespace App\Utils;


interface CacheInterface
{
    public function set($key, $value, $timeout = 36000);
    public function get($key);
}
