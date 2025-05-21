<?php
namespace App\Providers;

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Auth;
use Illuminate\Support\Facades\Log;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Auth::class, function ($app) {
            $credentialsPath = config('services.firebase.credentials');
            $databaseUrl = config('services.firebase.database_url');
          //  echo "\$credentialsPath:$credentialsPath:\$databaseUrl:$databaseUrl";

            Log::info('Firebase credentials path: ' . $credentialsPath);
            Log::info('Firebase database URL: ' . $databaseUrl);

            if (!$credentialsPath || !file_exists($credentialsPath)) {
                Log::error('Firebase credentials path is not set or does not exist.');
                throw new \Exception('Firebase credentials path is not set or does not exist.');
            }

            $serviceAccount = ServiceAccount::fromJsonFile($credentialsPath);

            //print_r($serviceAccount);exit;

            $factory = (new Factory)
                ->withServiceAccount($serviceAccount)
                ->withDatabaseUri($databaseUrl);

            Log::info('I am on line no. 40');
            
            // Create an instance of Auth directly
            return $factory->createAuth();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
 