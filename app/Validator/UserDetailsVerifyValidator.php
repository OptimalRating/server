<?php


namespace App\Validator;


class UserDetailsVerifyValidator extends ValidatorAbstract
{
    public function __construct()
    {
        $this->fields = [
            'birthdate' => 'required',
            'gender' => 'required',
            'education' => 'required',
            'marital_status' => 'required'
        ];
    }
}
