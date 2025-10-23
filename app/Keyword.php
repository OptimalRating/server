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

    // public function translation($country_code = null){ //commented 23-10-2025

    //     $auth = Auth::user();

    //     $country = (Country::find($auth->country_id));

    //     $code = $auth ?  $country->code : $country_code;

    //     return $this->hasOne(Translation::class, 'keyword_id', 'id')
    //         ->where('country_code','=', $code);

    // }

    public function translation($country_code = null)
{ //23-10-2025
    \Log::info('translation() method called');
    $auth = Auth::user();

    // Default to provided code or user's country code
    if (!$country_code && $auth && $auth->country_id) {
        $country = Country::find($auth->country_id);
        $country_code = $country ? $country->code : null;
    }
 Log::info('$country_code==',$country_code);

    // If no country code, fallback to global (NULL or 'global')
    if ($country_code) {
        return $this->hasOne(Translation::class, 'keyword_id', 'id')
                    ->where('country_code', '=', $country_code);
    }
    // Return global translation (assuming NULL is used to mark super admin/global)
    return $this->hasOne(Translation::class, 'keyword_id', 'id')
                ->whereNull('country_code');
}
}
