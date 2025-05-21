<?php


namespace App\Validator;

use App\CustomObjects\CustomJsonResponse;
use Illuminate\Support\Facades\Validator;

abstract class ValidatorAbstract
{

    protected $fields = [];

    public function validate()
    {
        //validate the city name
        $validator = Validator::make(request()->json()->all(), $this->fields);

        if(!$validator->passes()){
            $customResponse = new CustomJsonResponse(422, 'fail', 'msg.error.bad.request','', $validator->errors()->all());
            return $customResponse->getResponse();
        }

        return false;
    }

}
