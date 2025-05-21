<?php


/**
 * Class FakeService
 * @package App\Service
 * @author Üveys SERVETOĞLU <uveysservetoglu@gmail.com>
 */

namespace App\Service;


use App\Role;
use App\User;
use Faker\Factory;
use Illuminate\Support\Facades\Hash;

class FakeService
{
    public function createUser($country)
    {
        $faker = Factory::create();
        for ($i = 0; $i <= 10; $i++) {
            $user = User::create([
                'firstname' => 'user' . $i . '_' . strtolower($country->name_en),
                'middlename' => 'user' . $i . '_' . strtolower($country->name_en),
                'username'  => 'user' . $i . '_' . str_replace(' ', '_',  strtolower($country->name_en)),
                'lastname'  => 'user' . $i . '_' . strtolower($country->name_en),
                'email'     => 'user' . $i . '_' . str_replace(' ', '_',  strtolower($country->name_en)) . '@optimalrating.com',
                'password'  => Hash::make(123456),
                'social_type'  => 'fake',
                'country_id' => $country->id
            ]);

            $user->userDetails()->create([
                'phone_number' => $faker->phoneNumber
            ]);

            $role_country_admin  = Role::where('name', 'user')->first();

            $user->roles()->attach($role_country_admin);
        }
    }
}
