<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Friends extends Model
{
    public function friend()
    {
        return $this->belongsTo(User::class,'friend','id');
    }

    public function scopeHasFriend( $query, $user, $friend ) {
        $where = " ( user = '$user' AND friend = '$friend' ) OR ( user = '$friend' AND friend = '$user' ) ";
        $friends = $query->whereRaw($where)->get();
        return $friends->count();
    }
}
