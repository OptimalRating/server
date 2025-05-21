<?php

namespace App\Console\Commands;

use App\Privacy;
use App\Service\UserPrivacyService;
use App\User;
use App\UserPrivacySetting;
use Illuminate\Console\Command;

class PrivacyCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:privacy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param UserPrivacyService $privacyService
     * @return mixed
     */
    public function handle(UserPrivacyService $privacyService)
    {
        $privacyModel = Privacy::all();

        $userAll = User::with("privacySettings")->get();

        foreach ($userAll as $key => $user) {
            $userPrivacies = $user->privacySettings;

            $privacyIDS = [];

            foreach ($userPrivacies as $privacy) {
                $privacyIDS[] = $privacy->id;
            }

            if (count($userPrivacies) !== count($privacyModel)) {

                $privacyNotIN = Privacy::select('id')->whereNotIn('id', $privacyIDS)->get();

                $privacyNotINID = [];

                foreach ($privacyNotIN as $item){
                    $privacyNotINID[]= $item->id;
                }

                $privacyService->userPrivacyCreate($user->id, $privacyNotINID);

            }
        //    $privacySetting = UserPrivacySetting::where('user_id','=',$user->id);

        }
    }
}
