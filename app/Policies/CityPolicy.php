<?php

namespace App\Policies;

use App\City;
use App\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\HandlesAuthorization;

class CityPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * @param User $user
     * @return bool
     * @throws AuthorizationException
     * yalnızca super admin ana kategori ekler düzenler diğerleri alt kategori ekleyebilir
     */

    public function create(User $user)
    {
        if($user->hasRole('country_admin')){
            return true;
        }

        throw new AuthorizationException('Unauthorized');

    }

    /**
     * @param User $user
     * @return bool
     * @throws AuthorizationException
     */

    public function update(User $user)
    {
        if($user->hasRole('country_admin')){
            if(request()->json('country_id') != $user->country_id){
                throw new AuthorizationException('Unauthorized');
            }
        }

        return true;
    }

    public function delete(User $user, City $city)
    {
        if($user->hasRole('country_admin')){
            if($city->country_id != $user->country_id){
                throw new AuthorizationException('Unauthorized');
            }
        }

        return true;
    }
}
