<?php


namespace App\Validator;


class LoginValidator extends ValidatorAbstract
{
    public function __construct()
    {
        $this->fields = [
            $this->username() => 'required',
            'password' => 'required'
        ];
    }

    private function username()
    {
        return request()->json('email') ? 'email' : 'username';
    }
}
