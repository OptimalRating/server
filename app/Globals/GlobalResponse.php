<?php


namespace App\Globals;


class GlobalResponse
{
    private $response;

    private $errors=[];

    private $result;

    private $message;

    private $code;

    private $data;

    public function __construct($data, $error=false)
    {
        if($error)
            $this->errors = $data;
        else
            $this->data = $data;
    }

    public function getResponse()
    {
        return $this;
    }



}
