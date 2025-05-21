<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserPrivacySetting extends Model
{
    protected $guarded = [];

    public function privacy()
    {
        return $this->belongsTo(Privacy::class,'privacy_id','id');
    }

    public function option()
    {
        return $this->belongsTo(PrivacyOptions::class,'option_id','id');
    }
}
