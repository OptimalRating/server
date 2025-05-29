<?php


namespace App\Repositories;


abstract class Repository
{
    public function __construct(protected $model)
    {
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
