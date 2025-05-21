<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WebHookController extends Controller
{
    public function webhook(Request $request, $token){
        
        try {
            if($token !== env('WEBHOOK_TOKEN')) throw new \Exception('Not found error!');
    
            $changes = $request->push['changes'][0];

            $to_branch = $changes['new']['name'];
    
            if($to_branch == 'uat'){
                
                $output = shell_exec('cd /home/optimalr/webhook/uat && ./deploy.sh');

                return response()->json(['status' => true, 'message' => 'Successful ' .$output]);
            
            } else if($to_branch == 'master'){

                $output = shell_exec('cd /home/optimalr/webhook/master && ./deploy.sh');
                
                return response()->json(['status' => true, 'message' => 'successfull ' .$output]);
            }
           
            return response()->json(['status' => false, 'message' => 'Nothing to deploy!!']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => $th->getMessage()]);
        }

    }

}
