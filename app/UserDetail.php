<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Privacy\Privacy as Privacy;

class UserDetail extends Model
{
    use Privacy;

    protected $guarded = ['id'];
    public $timestamps = false;

    protected $appends = ['birthdate','education','gender','email','about'];

    protected function getBirthDateAttribute()
    {
        $model = $this->getPrivacyForGenericAttribute(auth()->user(), $this, 'birthdate');

        return $model ;
    }

    protected function getEducationAttribute()
    {
        $model = $this->getPrivacyForGenericAttribute(auth()->user(), $this, 'education');

        return $model ;
    }

    protected function getOccupationAttribute()
    {
        $model = $this->getPrivacyForGenericAttribute(auth()->user(), $this, 'occupation');

        return $model ;
    }

    protected function getGenderAttribute()
    {
        $model = $this->getPrivacyForGenericAttribute(auth()->user(), $this, 'gender');

        return $model ;
    }

    protected function getEmailAttribute()
    {
        $model = $this->getPrivacyForGenericAttribute(auth()->user(), $this, 'email');

        return $model ;
    }

    protected function getPhoneNumberAttribute()
    {
        $model = $this->getPrivacyForGenericAttribute(auth()->user(), $this, 'phone_number');

        return $model ;
    }

    protected function getAboutAttribute()
    {
        $model = $this->getPrivacyForGenericAttribute(auth()->user(), $this, 'about');

        return $model ;
    }

    protected function getFacebookUrlAttribute()
    {
        $model = $this->getPrivacyForGenericAttribute(auth()->user(), $this, 'facebook_url');

        return $model ;
    }

    protected function getInstagramUrlAttribute()
    {
        $model = $this->getPrivacyForGenericAttribute(auth()->user(), $this, 'instagram_url');

        return $model ;
    }

    protected function getSkypeUrlAttribute()
    {
        $model = $this->getPrivacyForGenericAttribute(auth()->user(), $this, 'skype_url');

        return $model ;
    }

    protected function getAnotherUrlAttribute()
    {
        $model = $this->getPrivacyForGenericAttribute(auth()->user(), $this, 'another_url');

        return $model ;
    }

    protected function getWebUrlAttribute()
    {
        $model = $this->getPrivacyForGenericAttribute(auth()->user(), $this, 'web_url');

        return $model ;
    }

    protected function getTwitterUrlAttribute()
    {
        $model = $this->getPrivacyForGenericAttribute(auth()->user(), $this, 'twitter_url');

        return $model ;
    }

    public function getUser()
    {
        return $this->belongsTo(User::class);
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }
}
