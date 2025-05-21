<?php


namespace App\Validator;


class CategoryValidator extends ValidatorAbstract
{
    public function __construct()
    {
        $this->fields = [
            'name' => 'required',
            'code'=> 'required'
        ];
    }
}
