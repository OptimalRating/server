<?php


namespace App\CustomObjects;


use App\Service\KeywordService;

class CustomJsonResponse
{
    private $code;
    private $message;
    private $data;
    private $errors;
    private $pagination;

    public function __construct($code, $message, $data=null, $errors=null, $pagination= null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
        $this->errors = $errors;
        $this->pagination = $pagination;
    }

    public function getResponse()
    {

        $responseData = [
            'message' => $this->message,
            'errors' => $this->errors,
            'result'  => [
                'set' => $this->data
                ],
        ];

        if(!is_null($this->pagination)){
            $responseData['result']['pagination'] = $this->pagination;
        }

        return response()->json($responseData, $this->code);
    }
}
