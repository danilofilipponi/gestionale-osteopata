<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_USER_EMAIL', 'danilo.filipponi@gmail.com')],
            [
                'name' => env('ADMIN_USER_NAME', 'Danilo Filipponi'),
                'password' => env('ADMIN_USER_PASSWORD', 'Zero1101985'),
            ],
        );
    }
}
