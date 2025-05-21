<?php


namespace App\Validator;


class CityValidator extends ValidatorAbstract
{

    public function __construct()
    {
        $this->fields = ['name' => 'required'];
    }
}
