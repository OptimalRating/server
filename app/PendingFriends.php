<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PendingFriends extends Model
{

    public function friend()
    {
        return $this->belongsTo(User::class,'friend','id');
    }
    public function user()
    {
        return $this->belongsTo(User::class,'user','id');
    }
}
