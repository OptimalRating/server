<?php

namespace App\Policies;

use App\Category;
use App\CustomObjects\CustomJsonResponse;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;

class CategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {

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
            if(request()->json('parent') == null){
                throw new AuthorizationException('Unauthorized');
            }
        }

        return true;
    }

    /**
     * @param User $user
     * @return bool
     * @throws AuthorizationException
     */
    public function update(User $user)
    {
        /*if($user->hasRole('country_admin')){
            if(request()->json('parent') == null){
                throw new AuthorizationException('Unauthorized');
            }
        }*/

        return true;
    }
}
