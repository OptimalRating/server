<?php

namespace App;

use App\Service\CountryService;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Request;

class Page extends Model
{
    use Sluggable;

    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'slug',
        'title' ,
        'body',
        'order',
        'status',
    ];

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ],
            'translate_key' => [
                'source' => 'pageTranslateKey'
            ]
        ];
    }


    public function getPageTranslateKeyAttribute() {
        return 'page_' . $this->title;
    }

    public function translation($code = null)
    {
        $country = (new CountryService(request()))->getCountry();

        $country = Country::where('code','=',$country->code ?? $code)->first();

        $country = !is_null($country) ? $country->code : null;

        return $this->hasOne(PageTranslations::class, 'page_id')
            ->where('country_code','=', $country);
    }
}
