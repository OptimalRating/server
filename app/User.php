<?php

namespace App;

//use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Privacy\Privacy as Privacy;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, Privacy, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [  
        'firstname', 'middlename', 'lastname','email', 'password', 'country_id', 'city_id', 'username', 'status', 'uid', 'social_type', 'deleted_at',
        'name',
        'provider',
        'provider_id',
    ];
    // protected $fillable = ['email', 'firstName', 'lastName'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['password', 'uid', 'created_at', 'updated_at','social_type', 'phone_verify'];

    protected $appends = ['lastname','middlename'];

    protected function getLastNameAttribute()
    {
        $model = $this->getPrivacyForGenericAttribute(auth()->user(), $this, 'lastname' );

        return $model ;
    }

    protected function getMiddleNameAttribute()
    {
        $model = $this->getPrivacyForGenericAttribute(auth()->user(), $this, 'middlename');

        return $model ;
    }

    protected function getEmailAttribute()
    {
        $model = $this->getPrivacyForGenericAttribute(auth()->user(), $this, 'email');

        return $model ;
    }

    /**
     * Find the user instance for the given username.
     *
     * @param  string  $username
     * @return User
     */
    public function findForPassport($username)
    {
        return $this->where('username', $username)->orWhere('email', $username)->first();
    }

    public static function findForLogin($username)
    {

        $user = self::with('roles')
            ->where('username', $username)
            ->whereIn('status', ['approved','freeze','pendingFreeze','pending'])
            ->orWhere('email', $username)
            ->first();

        if ($user->status === 'deleted') {
           return false;
        }
        return $user;
    }

    public function userDetails()
    {
        return $this->hasOne(UserDetail::class, 'user_id', 'id');
    }

    public function friends()
    {
        return $this->hasMany(Friends::class, 'user', 'id');
    }

    public function pendingFriends()
    {
        return $this->hasMany(PendingFriends::class, 'friend', 'id')
            ->where('status','=','pending');
    }

    public function pendingRequestFriends()
    {
        return $this->hasMany(PendingFriends::class, 'user', 'id')
            ->where('status','=','pending');
    }

    public function AauthAcessToken(){
        return $this->hasMany('\App\OauthAccessToken');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function authorizeRoles($roles)
    {
        if (is_array($roles)) {
            return $this->hasAnyRole($roles) ||
                abort(401, 'This action is unauthorized.');
        }
        return $this->hasRole($roles) ||
            abort(401, 'This action is unauthorized.');
    }

    /**
     * Check multiple roles
     * @param array $roles
     * @return bool
     */
    public function hasAnyRole($roles)
    {
        return null !== $this->roles()->whereIn('name', $roles)->first();
    }

    /**
     * Check one role
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        return null !== $this->roles()->where('name', $role)->first();
    }

    public function country()
    {
        return $this->belongsTo('App\Country');
    }

    public function city()
    {
        return $this->belongsTo('App\City');
    }

    public function getNormalUsers()
    {
        return $this->roles()->where('name', '<>', 'super_admin');
    }

    public function votes()
    {
        return $this->hasMany(SurveyVote::class);
    }

    public function privacySettings()
    {
        return $this->hasMany(UserPrivacySetting::class);
    }

    public function choices()
    {
        return $this->hasMany(SurveyChoice::class);
    }
}
