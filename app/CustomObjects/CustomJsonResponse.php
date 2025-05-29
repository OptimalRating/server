<?php


namespace App\CustomObjects;


use App\Service\KeywordService;

class CustomJsonResponse
{
    public function __construct(private $code, private $message, private $data=null, private $errors=null, private $pagination= null)
    {
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
