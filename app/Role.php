<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public function users()  //this is a relation to users
    {
        return $this->belongsToMany(User::class);
    }
}
