<?php


namespace App\Validator;


class SubjectValidator extends ValidatorAbstract
{
    public function __construct()
    {
        $this->fields = ['title' => 'required'];
    }
}
