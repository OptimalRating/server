<?php


namespace App\Repositories;


abstract class Repository
{
    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function findData($id)
    {
        return $this->model->find($id);
    }

    public function saveData($data)
    {
        return $this->model::create($data);
    }

    public function delete()
    {
        return $this->model::delete();
    }

    public function update($object, $data)
    {
        return $object->update($data);
    }
}
