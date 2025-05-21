<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Privacy;
use App\PrivacyOptions;
use App\Validator\PrivacyValidator;


class PrivacyController extends Controller
{
    public function create()
    {
        $validator = new PrivacyValidator();

        if($response = $validator->validate())
            return $response;

        $model = Privacy::create(request()->json()->all());



        foreach (request('option') as $option) {
            $modelOption = new PrivacyOptions();


            $modelOption->privacy_id = $model->id;
            $modelOption->option = $option;

            $modelOption->save();

            if ($option == request('default_option')) {

                $model->default_option = $modelOption->id;
                $model->save();
            }
        }

        return response()->json([
            'code'   => 200,
            'message'=> 'msg.info.privacy.add',
        ]);
    }
}
