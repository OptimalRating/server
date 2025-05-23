<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Category extends Model
{
    use Sluggable, SoftDeletes;

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    protected $guarded = ['id'];

    public function getChildren($status = 'active', $isThereCountry = false, $country = null)
    {
        $category = $this->hasMany('App\Category', 'parent', 'id')
            ->with('surveys');
            //->whereStatus($status);

        if ($isThereCountry) {
            $category = $category->where('country_id', '=', $country ? $country->id : null);
        }
        
        return $category->get();
    }

    public function surveys()
    {
        return $this->hasMany('App\Survey', 'category_id')->with('user');
    }

    public function parent()
    {
        return $this->belongsTo('App\Category', 'parent');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id')->with('userDetails');
    }

    public function scopePrepareChildrenWhere($query)
    {
        if (!auth()->user()->hasRole('super_admin'))
        {
            return $query->where('country_id', Auth::user()->country_id);
        } else {
            return $query->where('is_world', true);
        }
    }

  /*  protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CountryScope());
    }*/
}
