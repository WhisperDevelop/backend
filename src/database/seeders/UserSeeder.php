<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * データベースにユーザーデータを投入する。
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | テスト用ユーザー（固定）
        |--------------------------------------------------------------------------
        |
        | APIテスト用として、ログインしやすいユーザーを用意する。
        |
        */

        User::create([
            'name' => 'Admin01',
            'email' => 'test@test.com',
            'password' => Hash::make('W@admin01'),
        ]);

        User::create([
            'name' => 'Admin02',
            'email' => 'admin@test.com',
            'password' => Hash::make('W@admin02'),
        ]);

    }
}