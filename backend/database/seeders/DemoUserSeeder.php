<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'host@doorkey.demo'],
            [
                'name' => 'DoorKey Host',
                'password' => Hash::make('host123'),
                'role' => 'Host',
                'active' => true,
            ],
        );

        User::firstOrCreate(
            ['email' => 'admin@doorkey.demo'],
            [
                'name' => 'DoorKey Admin',
                'password' => Hash::make('admin123'),
                'role' => 'Admin',
                'active' => true,
            ],
        );
    }
}
