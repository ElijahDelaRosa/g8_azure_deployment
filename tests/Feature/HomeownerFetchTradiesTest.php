<?php

namespace Tests\Feature;

use App\Models\Homeowner;
use App\Models\Tradie;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeownerFetchTradiesTest extends TestCase
{
    use RefreshDatabase;

    protected $homeowner;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate a homeowner
        $this->homeowner = Homeowner::factory()->create();
        $this->token = $this->homeowner->createToken('test-token')->plainTextToken;
    }

    public function test_homeowner_can_fetch_all_active_tradies()
    {
        Tradie::factory()->count(5)->create(['status' => 'active']);
        Tradie::factory()->count(2)->create(['status' => 'inactive']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/homeowner/tradies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'first_name',
                            'last_name',
                            'business_name',
                            'hourly_rate',
                            'availability_status',
                            'services',
                        ]
                    ],
                    'current_page',
                    'per_page',
                    'total',
                ]
            ])
            ->assertJsonPath('data.total', 5);
    }

    public function test_homeowner_can_fetch_single_tradie()
    {
        $tradie = Tradie::factory()->create(['status' => 'active']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/homeowner/tradies/{$tradie->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'business_name',
                    'hourly_rate',
                    'services',
                ]
            ])
            ->assertJsonPath('data.id', $tradie->id);
    }

    public function test_homeowner_cannot_fetch_inactive_tradie()
    {
        $tradie = Tradie::factory()->create(['status' => 'inactive']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/homeowner/tradies/{$tradie->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Tradie not found',
            ]);
    }

    public function test_homeowner_can_filter_tradies_by_availability()
    {
        Tradie::factory()->count(3)->create([
            'status' => 'active',
            'availability_status' => 'available'
        ]);
        Tradie::factory()->count(2)->create([
            'status' => 'active',
            'availability_status' => 'busy'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/homeowner/tradies?availability_status=available');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 3);
    }

    public function test_homeowner_can_filter_tradies_by_region()
    {
        Tradie::factory()->count(3)->create([
            'status' => 'active',
            'region' => 'Auckland'
        ]);
        Tradie::factory()->count(2)->create([
            'status' => 'active',
            'region' => 'Wellington'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/homeowner/tradies?region=Auckland');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 3);
    }

    public function test_homeowner_can_filter_tradies_by_hourly_rate_range()
    {
        Tradie::factory()->create(['status' => 'active', 'hourly_rate' => 50]);
        Tradie::factory()->create(['status' => 'active', 'hourly_rate' => 75]);
        Tradie::factory()->create(['status' => 'active', 'hourly_rate' => 100]);
        Tradie::factory()->create(['status' => 'active', 'hourly_rate' => 150]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/homeowner/tradies?min_rate=60&max_rate=120');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 2);
    }

    public function test_homeowner_can_filter_tradies_by_minimum_experience()
    {
        Tradie::factory()->create(['status' => 'active', 'years_experience' => 2]);
        Tradie::factory()->create(['status' => 'active', 'years_experience' => 5]);
        Tradie::factory()->create(['status' => 'active', 'years_experience' => 10]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/homeowner/tradies?min_experience=5');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 2);
    }

    public function test_homeowner_can_search_tradies_by_name()
    {
        Tradie::factory()->create([
            'status' => 'active',
            'first_name' => 'John',
            'last_name' => 'Smith'
        ]);
        Tradie::factory()->create([
            'status' => 'active',
            'first_name' => 'Jane',
            'last_name' => 'Doe'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/homeowner/tradies?search=John');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 1);
    }

    public function test_homeowner_can_search_tradies_by_business_name()
    {
        Tradie::factory()->create([
            'status' => 'active',
            'business_name' => 'ABC Plumbing'
        ]);
        Tradie::factory()->create([
            'status' => 'active',
            'business_name' => 'XYZ Electrical'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/homeowner/tradies?search=Plumbing');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 1);
    }

    public function test_homeowner_can_paginate_tradies()
    {
        Tradie::factory()->count(25)->create(['status' => 'active']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/homeowner/tradies?per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('data.per_page', 10)
            ->assertJsonPath('data.total', 25)
            ->assertJsonCount(10, 'data.data');
    }

    public function test_unauthenticated_user_cannot_fetch_tradies()
    {
        $response = $this->getJson('/api/homeowner/tradies');

        $response->assertStatus(401);
    }

    public function test_homeowner_can_combine_multiple_filters()
    {
        Tradie::factory()->create([
            'status' => 'active',
            'region' => 'Auckland',
            'availability_status' => 'available',
            'hourly_rate' => 80,
            'years_experience' => 5
        ]);
        Tradie::factory()->create([
            'status' => 'active',
            'region' => 'Auckland',
            'availability_status' => 'busy',
            'hourly_rate' => 80,
            'years_experience' => 5
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/homeowner/tradies?region=Auckland&availability_status=available&min_rate=70&max_rate=90');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 1);
    }
}
