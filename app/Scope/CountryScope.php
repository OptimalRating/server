<?php


/**
 * Class CountryScope
 * @package App\Scope
 * @author Üveys SERVETOĞLU <uveysservetoglu@gmail.com>
 */

namespace App\Scope;


use App\Country;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CountryScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $countryID = app('Illuminate\Http\Request')->header('country');
        $country = Country::where('code','=',$countryID)->first();

        $builder->where('country_id', '=', $country->id);
    }
}
