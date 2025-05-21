<?php

namespace App;


use Illuminate\Database\Eloquent\Model;

class PageTranslations extends Model
{
    protected $table = 'page_translations';

    public function page()
    {
        return $this->belongsTo(Page::class,'page_id','id');
    }

}
