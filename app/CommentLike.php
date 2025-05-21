<?php

namespace App;

use App\Comment;
use Illuminate\Database\Eloquent\Model;

class CommentLike extends Model
{
    protected $guarded = ['id'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
