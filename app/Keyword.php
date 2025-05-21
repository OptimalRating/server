<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Keyword extends Model
{
    protected $guarded = ['id'];

    protected $fillable = ['key', 'default', 'category_id'];

    protected $table = 'keywords';

    public function translations(){
        return $this->hasMany(Translation::class);

    }

    public function translation($country_code = null){

        $auth = Auth::user();

        $country = (Country::find($auth->country_id));

        $code = $auth ?  $country->code : $country_code;

        return $this->hasOne(Translation::class, 'keyword_id', 'id')
            ->where('country_code','=', $code);

    }
}
