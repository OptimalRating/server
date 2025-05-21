<?php


namespace App\Validator;


class SurveyValidator extends ValidatorAbstract
{
    public function __construct()
    {
        $this->fields = [
            'title' => 'required',
            //'category_id'=> 'required'
        ];
    }
}
