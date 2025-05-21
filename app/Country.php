<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes; //not needed

class Country extends Model
{
    // use SoftDeletes; //not needed
    
    protected $fillable = ['name_en', 'name', 'flag', 'code', 'status', 'sort_order', 'country_admin'];

    public function cities()
    {
        return $this->belongsTo(City::class);
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'country_admin');
    }

}
