<?php

use Illuminate\Database\Seeder;
use App\User;
use App\Role;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()  //a super user and country user attached to related users
    {
        $user = User::where('email', 'super_admin@admin.com')->first();
        if ( empty( $user ) ) {
            $role_super_admin = Role::where('name', 'super_admin')->first();
            $role_country_admin  = Role::where('name', 'country_admin')->first();
            $super_admin = new User();
            $super_admin->firstname = 'Super Admin';
            $super_admin->username = 'super_admin';
            $super_admin->email = 'super_admin@admin.com';
            $super_admin->password = bcrypt('123456');
            $super_admin->save();
            $super_admin->roles()->attach($role_super_admin);
    
            $country_admin = new User();
            $country_admin->firstname = 'Turkey Admin';
            $country_admin->username = 'turkey_admin';
            $country_admin->email = 'Turkey_admin@admin.com';
            $country_admin->password = bcrypt('123456');
            $country_admin->save();
            $country_admin->roles()->attach($role_country_admin);
        }
    }
}
