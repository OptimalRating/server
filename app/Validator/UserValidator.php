<?php


namespace App\Validator;


class UserValidator extends ValidatorAbstract
{
    public function __construct()
    {
        $this->fields = [
            'firstname' => 'required',
            'lastname' => 'required',
            'country_id' => 'required',
            'city_id' => 'required'
        ];
    }
}
