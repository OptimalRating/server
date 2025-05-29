<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Exception\Auth\FirebaseAuthException;
use Illuminate\Support\Facades\Log;

class OtpController extends Controller
{
    protected $auth;

    // public function __construct()
    // {
    //     $serviceAccount = ServiceAccount::fromJsonFile('/var/www/html/staging/server/serviceAccountKey.json');

    //     $firebase = (new Factory)
    //         ->withServiceAccount($serviceAccount)
    //         ->create();
    //     $this->auth = $firebase->getAuth();
    // }

    public function __construct()
    {
        // $factory = (new Factory)
        //     ->withServiceAccount('/var/www/html/staging/server/serviceAccountKey.json');
        
        // // Initialize the Auth service
        // $this->auth = $factory->createAuth();
        try {
            $serviceAccountPath = base_path('serviceAccountKey.json');

            if (!file_exists($serviceAccountPath) || !is_readable($serviceAccountPath)) {
                throw new \RuntimeException("Firebase service account key file is missing or unreadable.");
            }

            $factory = (new Factory)->withServiceAccount($serviceAccountPath);
            $this->auth = $factory->createAuth();
        } catch (Exception $e) {
            Log::error('Firebase Auth initialization failed: ' . $e->getMessage());
            // Optionally: throw or handle gracefully
            abort(500, 'Unable to initialize Firebase.');
        }
    }

    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required',
        ]);
       // echo "add";exit;
        if ($validator->fails()) {
            Log::info('validator fails');
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $phoneNumber = $request->input('phone_number');

            // Use Firebase Auth to send OTP
            $this->auth->signInWithPhoneNumber($phoneNumber);
           // Log::info('INSIDE OTP TRY BLOCK ');
            return response()->json(['message' => 'OTP sent successfully'], 200);
        } catch (FirebaseAuthException $e) {
            // Log::info('INSIDE OTP CATCH BLOCK ');
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    
    public function verifyOtp(Request $request)
    {
     {
    $validator = Validator::make($request->all(), [
        'idToken' => 'required',
    ]);
    // Log::info('Request received', ['request' => $request->all()]);
    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    try {
        $idToken = $request->input('idToken');
        $verifiedIdToken = $this->auth->verifyIdToken($idToken);
        $uid = $verifiedIdToken->claims()->get('sub');
        return response()->json(['message' => 'OTP verified successfully', 'user_uid' => $uid], 200);
    } catch (\Kreait\Firebase\Exception\Auth\FailedToVerifyToken $e) {
        return response()->json(['error' => 'The token is invalid: ' . $e->getMessage()], 401);
    } catch (\Exception $e) {
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    }
   }
  }

}

