<?php


namespace App\Validator;


class PrivacyValidator extends ValidatorAbstract
{
    public function __construct()
    {
        $this->fields = [
            'name' => 'required'
        ];
    }
}
