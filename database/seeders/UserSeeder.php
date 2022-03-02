<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = new User() ;
        $admin->first_name = "Admin";
        $admin->last_name = "Ofapp";
        $admin->email = "admin@app.com";
        $admin->password= Hash::make('password');
        $admin->status = 1;
        $admin->save();
        $admin->attachRole('administrator');
    }
}
