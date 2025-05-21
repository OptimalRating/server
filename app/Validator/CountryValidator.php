<?php


namespace App\Validator;


class CountryValidator extends ValidatorAbstract
{
    public function __construct()
    {
        $this->fields = [
            'name' => 'required',
            'code' => 'required|unique:countries',
            'email' => 'required|email',
            'password' => 'required|confirmed',
        ];
    }
}
