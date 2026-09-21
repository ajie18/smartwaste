<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['admin', 'driver', 'resident'] as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        User::query()->each(function (User $user): void {
            if (in_array($user->role, ['admin', 'driver', 'resident'], true)) {
                $user->syncRoles([$user->role]);
            }
        });
    }

    public function down(): void
    {
        User::query()->each(function (User $user): void {
            $user->roles()->detach();
        });

        Role::whereIn('name', ['admin', 'driver', 'resident'])
            ->where('guard_name', 'web')
            ->delete();
    }
};
