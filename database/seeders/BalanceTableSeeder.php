<?php

namespace Database\Seeders;

use App\Models\Balance;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BalanceTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Balance::create([
            'user_id' => 5,
            'amount' =>0,
        ]);
        Balance::create([
            'user_id' => 6,
            'amount' =>0,
        ]);
        Balance::create([
            'user_id' => 7,
            'amount' =>0,
        ]);
    }
}
