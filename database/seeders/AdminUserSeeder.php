<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $name = trim((string) env('ADMIN_NAME'));
        $email = trim((string) env('ADMIN_EMAIL'));
        $password = (string) env('ADMIN_PASSWORD');

        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 16) {
            throw new RuntimeException(
                'Set ADMIN_NAME, a valid ADMIN_EMAIL, and an ADMIN_PASSWORD of at least 16 characters before deploying.'
            );
        }

        $existingUser = User::where('email', $email)->first();

        if ($existingUser && $existingUser->role !== 'admin') {
            throw new RuntimeException('The configured admin email already belongs to a non-admin user.');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => 'admin',
            ]
        );
    }
}
