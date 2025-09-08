<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OwnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Owner Hawwary',
            'username' => 'owner',
            'role' => 'owner',
            'password' => Hash::make('password123'),
        ]);

        // Jika ingin membuat multiple owner, bisa uncomment baris di bawah
        // User::create([
        //     'name' => 'Owner Kedua',
        //     'username' => 'owner2',
        //     'role' => 'owner',
        //     'password' => Hash::make('password123'),
        // ]);
    }
}
