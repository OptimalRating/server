<?php

namespace App\Http\Controllers\Api;

use App\CustomObjects\ApiPagination;
use App\Service\CustomJsonResponse;
use App\Service\UserService;
use App\User;
use App\Http\Controllers\Controller;
use App\UserDetail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;


class MembersController extends Controller
{
    public function __construct(private readonly CustomJsonResponse $jsonResponse)
    {
    }

    public function index()
    {
        if(auth()->user()->hasRole('super_admin')){
            $model = User::with(['country', 'userDetails']);
        }

        // else if(auth()->user()->hasRole('country_admin')){         //old code 
        //     $model = User::whereHas('roles', function (Builder $query) {
        //         $query->whereNotIn('name', ['super_admin','country_admin']);
        //     })->where('country_id', auth()->user()->country_id);

        // }

        else if(auth()->user()->hasRole('country_admin')) {              //updated code
            $model = User::whereHas('roles', function (Builder $query) {
                // Exclude 'super_admin' and 'country_admin' roles
                $query->whereNotIn('name', ['super_admin','country_admin']);
            })
            ->where('country_id', auth()->user()->country_id)
            ->orWhere(function ($query) {
                // Add an additional case: Check if the user has a specific 'provider' and the 'country_admin' of their country
                $query->where('provider', 'google') // Example provider, adjust as needed
                      ->where('country_id', auth()->user()->country_id)  // Same country as the logged-in user
                      ->whereHas('country', function($countryQuery) {
                          // Check if the country has a country_admin
                          $countryQuery->where('country_admin', auth()->user()->id); 
                      });
            });
        }
        
        if (!is_null(request('keyword'))  && request("keyword") !=="") {
            $query[] = ['email', 'like',  '%'.request("keyword").'%'];
            $model->where($query);
        }

        if(!is_null(request('userStatus')) && request('userStatus') != ""){
            $model->whereIn('status', request('userStatus'));
        }

        if(!is_null(request('userGender') ) && request('userGender') !==""){
            $model->whereHas('userDetails', function (Builder $builder){
               $builder->where('gender', request('userGender'));
            });
        }

        if(!is_null(request('userEducation')) && request('userEducation') !==""){
            $model->whereHas('userDetails', function (Builder $builder){
               $builder->where('education', request('userEducation'));
            });
        }

        if(!is_null(request('country')) && request('country') !==""){
            $model->where('country_id', request('country'));
        }

        if(!is_null(request('city')) && request('city') !==""){
            $model->where('city_id', request('city'));
        }

        if(request('birthdate')){
            $birthdate = request('birthdate');

            $builder = UserDetail::select(['user_id','birthdate']);

            if (!empty($birthdate['from_date'])) {
                $builder ->where('birthdate', '>', $birthdate['from_date']);
            }

            if (!empty($birthdate['until_date'])) {
                $builder->where('birthdate', '<', $birthdate['until_date']);
            }

            $ids = [];
            foreach ($builder->get() as $item) {
                $ids[] = $item->user_id;

            }

            $model->whereIn('id', $ids);
        }

        $pagination = new ApiPagination(request("limit", 100), is_countable($model->get()) ? count($model->get()) : 0, request("offset", 0));

        $model = $model->orderBy(request('sort','id'), request('order', 'desc'))
            ->offset(request('offset', 0))->take(request('limit'))->get();

        $this->jsonResponse->setData(
            200,
            'msg.info.list.members', $model, null, $pagination->getConvertObject()
        );

        return $this->jsonResponse->getResponse();
    }

    public function show(User $user)
    {
        $this->jsonResponse->setData(200,   'msg.info.success.user.show', $user);
        return $this->jsonResponse->getResponse();
    }

    public function update(User $user)
    {
        $user->update(request()->all());

        $this->jsonResponse->setData(200,'msg.info.success.user.updated', $user);

        return $this->jsonResponse->getResponse();
    }

    public function upgrade(User $user)
    {
        $user->is_real = request('is_real');
        $user->status = 'active';
        $user->save();

        $this->jsonResponse->setData(200,   'msg.info.success.grade', $user);

        return $this->jsonResponse->getResponse();
    }

    public function destroy($user)
    {
        $user = User::find($user);
        $userDetails = UserDetail::where('user_id',  $user->id)->first();
        if (!is_null($user)) {
            $userService = new UserService();
            $userService->deleteUser($user);
            
             // Set email to null and save changes
             $user->email = null;
             $userDetails->phone_number = null;
             $user->save();  // Ensure the change is persisted in the database
             $userDetails->save();
        }

        return $this->jsonResponse->getResponse();
    }

}
