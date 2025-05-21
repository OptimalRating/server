<?php


namespace App\Repositories;


use App\CommentLike;

class CommentLikeRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(new CommentLike());
    }

    public function find($id)
    {
        $this->find($id);
    }

    public function save($request)
    {
        $data = $this->checkUserLikeExist($request);
        if(!count($data)){
            $data = [
                'comment_id' => $request['comment_id'],
                'user_id' => auth()->id()
            ];
            return [
                'action' => 'create',
                'statusCode' => $this->saveData($data),
            ];
        }else{
            return [
                'action' => 'delete',
                'statusCode' => $data[0]->delete(),
            ];
        }

        return [];
    }

    public function getNumberOfLike($commentLike)
    {
        return $this->model->where('comment_id', $commentLike->comment_id)->count();
    }

    public function checkUserLikeExist($request)
    {
        return $this->model->where('comment_id', $request->json('comment_id'))->where('user_id', auth()->id())->get();
    }
}
