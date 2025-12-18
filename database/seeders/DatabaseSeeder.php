<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Homeowner;
use App\Models\Payment;
use App\Models\Tradie;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::factory()->create([
            'first_name' => 'admin ',
            'last_name' => 'admin',   
            'middle_name' => 'admin',
            'email' => 'admin@fixo.com',
            'password' => Hash::make("admin"),
            'role' => 'admin',
            'status' => 'active',
        ]);

        Homeowner::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'homeowner1@gmail.com',
            'password' => Hash::make("password"),
            'status' => 'active',
        ]);

        Tradie::factory()->create([
            'first_name' => 'John',
            'email' => 'john.example@email.com',
            'phone' => '09987654321',
            'password' => Hash::make("tradie123"),
            'status' => 'active'
        ]);

        Payment::factory(20)->create();
        User::factory(10)->create();
        // Seed other users
        Homeowner::factory(20)->create();
        Tradie::factory(10)->create();

        $this->call([
            ServiceSeeder::class,
            HomeownerJobOfferSeeder::class,
        ]);

        // Seed bookings after homeowners, tradies and services exist
        $this->call(BookingSeeder::class);
    }
}
