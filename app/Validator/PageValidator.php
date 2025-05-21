<?php


namespace App\Validator;


class PageValidator extends ValidatorAbstract
{
    public function __construct()
    {
        $this->fields = [
            'title' => 'required',
            'body' => 'required'
        ];
    }
}
