<?php


/**
 * Class KeywordService
 * @package App\Service
 * @author Üveys SERVETOĞLU <uveysservetoglu@gmail.com>
 */

namespace App\Service;


use App\Keyword;

class KeywordService
{

    public function __construct($keyword, $default = null, $type = null)
    {
        $model = Keyword::where('key','=',$keyword)->get();

        if(empty($model)){
            $model = new Keyword();
            $model->key = $keyword;
            $model->default = $default ?? $keyword;
            $model->type  = $type ?? 'messages';
            $model->save();
        }
    }
}
