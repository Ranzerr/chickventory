<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()->where('username', 'admin')->orWhere('email', 'admin@example.com')->first()
            ?? new User(['email' => 'admin@example.com']);

        $user->fill([
            'name' => 'Administrator',
            'username' => 'admin',
            'password' => 'admin',
            'role' => 'Administrator',
            'status' => 'active',
        ])->save();

        $this->call(InventorySeeder::class);
    }
}
