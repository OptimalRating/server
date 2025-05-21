<?php

namespace App;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;

class Privacy extends Model
{
    use Sluggable;

    protected $fillable = ['name','description'];

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
            ],
            'translate_key' => [
                'source' => 'privacyTranslateKey'
            ],
            'description_translate_key' => [
                'source' => 'privacyDescriptionTranslateKey'
            ]
        ];
    }


    public function getPrivacyTranslateKeyAttribute() {
        return 'privacy_' . $this->name;
    }

    public function getPrivacyDescriptionTranslateKeyAttribute() {
        return 'privacy_description_' . $this->name;
    }

    public function options()
    {
        return $this->hasMany(PrivacyOptions::class);
    }
}
