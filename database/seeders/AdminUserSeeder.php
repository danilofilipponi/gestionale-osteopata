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
            ['email' => env('ADMIN_USER_EMAIL', 'admin@example.com')],
            [
                'name' => env('ADMIN_USER_NAME', 'Studio Osteopata'),
                'password' => env('ADMIN_USER_PASSWORD', 'password'),
            ],
        );
    }
}
