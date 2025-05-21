<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyChoice extends Model
{
    use SoftDeletes;

    protected $fillable = ['survey_id', 'choice_title', 'choice_image', 'choice_description', 'mark', 'isImageUpdated', 'survey_type'];

    public $timestamps = false;

    public $casts = [
      "status" => "string"
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class, 'survey_id')->with(['choices']);
    }

    public function onlySurvey()
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function votes()
    {
        return $this->hasMany(SurveyVote::class, 'choice_id')->whereNotNull('mark');
    }

    public function votesSpecial()
    {
        return $this->hasMany(SurveyVote::class, 'choice_id');
    }
}
