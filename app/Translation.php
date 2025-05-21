<?php

namespace App;


use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $table = 'translations';

    public function keyword()
    {
        return $this->belongsTo(Keyword::class);
    }
}
