<?php


namespace App\Validator;


class SurveyVoteValidator extends ValidatorAbstract
{
    public function __construct()
    {
        $this->fields = [
            'choice_id' => 'required',
            'survey_id' => 'required',
            //'category_id'=> 'required'
        ];
    }
}
