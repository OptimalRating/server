<?php

namespace App\Http\Controllers\Api;

use App\CustomObjects\CustomJsonResponse;
use App\Http\Controllers\Controller;
use App\Service\FileService;
use Symfony\Component\HttpFoundation\Request;

class FileController extends Controller
{

    public function upload(Request $request)
    {
        $fileService = new FileService();
        $file = $request->files->get('file');

        $type = $request->get('type');
        $fileResult = $fileService->upload($file, $type);

        $customResponse = new CustomJsonResponse(200, 'msg.info.login.success', $fileResult);

        return $customResponse->getResponse();
    }
}
