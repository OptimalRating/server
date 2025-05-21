<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsVerify extends Model
{
    use HasFactory;

    protected $fillable = ['phone_number', 'sms', 'expired'];
}
