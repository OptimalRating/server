<?php


namespace App\Repositories;


use App\Survey;

class SurveyRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(new Survey());
    }

    public function find($id)
    {
        $this->find($id);
    }

    public function updateData($survey, $data)
    {
        return $this->update($survey, $data);
    }
}
