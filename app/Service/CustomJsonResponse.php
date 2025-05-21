<?php


namespace App\Service;


class CustomJsonResponse
{
    private $code;
    private $message;
    private $data;
    private $errors;
    private $pagination;

    public function setData($code, $message=null, $data=null, $errors=null, $pagination= null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;

        $this->errors = $errors;
        $this->pagination = $pagination;
        return $this;
    }

    public function getResponse()
    {
        $keyword = new KeywordService($this->message);
        $responseData = [
            'code'    => $this->code,
            'message' => $this->message,
            'errors' => $this->errors,
            'result'  => [
                'set' => $this->data
            ],
        ];


        if(!is_null($this->pagination)){
            $responseData['result']['pagination'] = [
                'pageCount' => $this->pagination->pageCount,
                'recordCount' => $this->pagination->recordCount,
                'itemsPerPage' => $this->pagination->itemsPerPage,
                'firstLink' => $this->pagination->firstLink,
                'lastLink' => $this->pagination->lastLink,
                'prevLink' => $this->pagination->prevLink,
                'nextLink' => $this->pagination->nextLink,
                'currentPage' => $this->pagination->currentPage
            ];
        }
        return $responseData;
    }
}
