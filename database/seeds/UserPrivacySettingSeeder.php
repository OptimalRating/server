<?php

use App\User;
use App\Privacy;
use App\PrivacyOptions;
use App\UserPrivacySetting;
use Illuminate\Database\Seeder;

class UserPrivacySettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $users = User::all();
        $default_privacies_id = Privacy::select('id')->get();
        $default_privacy_options_id = PrivacyOptions::select('id')->where('option', 'Everyone')->get();
        foreach ( $users as $user ) {
            foreach ( $default_privacies_id as $key => $privacy_id ) {
                $user_privacy = UserPrivacySetting::where([
                    'user_id' => $user->id,
                    'privacy_id' => $privacy_id->id,
                ])->first();

                if ( empty( $user_privacy ) ) {
                    UserPrivacySetting::create([
                        'user_id' => $user->id,
                        'privacy_id' => $privacy_id->id,
                        'option_id' => $default_privacy_options_id[$key]->id,
                    ]);
                }
            }
        }
    }
}
