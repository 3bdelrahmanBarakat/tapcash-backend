<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Manually create some users with specific data
        User::create([
            'id' => 5,
            'phone_number' => '01234567890',
            'pin_code' => bcrypt('123456'),
            'password' => 'password123',
            'first_name' => 'tony',
            'last_name' => 'soprano',
            'type' => 'user',
            'enabled' => true,
            'mobile_verified_at' => now(),
        ]);

        User::create([
            'id' => 6,
            'phone_number' => '01234567891',
            'pin_code' => bcrypt('123456'),
            'password' => 'password123',
            'first_name' => 'junior',
            'last_name' => 'tony',
            'parent_id' => 5,
            'type' => 'kid',
            'enabled' => true,
            'mobile_verified_at' => now(),
        ]);

        User::create([
            'id' => 7,
            'phone_number' => '01234567892',
            'pin_code' => bcrypt('123456'),
            'password' => 'password123',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'type' => 'company',
            'enabled' => true,
            'mobile_verified_at' => now(),
        ]);
    }
}
