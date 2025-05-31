<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $array = [
            [
                'name' => 'DanilAdmin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
            [
                'name' => 'Admin1',
                'email' => 'admin1@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
            [
                'name' => 'DanilUser',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        ];
        foreach($array as $item) {
            User::create($item);
        }   
    }
}