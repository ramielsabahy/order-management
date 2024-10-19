<?php
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'ramyelsabahy95@gmail.com',
        'password' => bcrypt('password'),
    ]);
    Artisan::call('passport:client', ['--personal' => true]);
});

it('can log in a user and returns the expected response', function () {
    $requestData = [
        'email' => 'ramyelsabahy95@gmail.com',
        'password' => 'password',
    ];

    $response = $this->postJson('/api/login', $requestData);

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data' => [
            'user' => [
                'id',
                'name',
                'email',
            ],
            'token',
        ],
    ]);

    $response->assertJson([
        'data' => [
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
        ],
    ]);

    expect($response->json('data.token'))->toBeString();
});

it('returns an error when credentials are invalid', function () {
    $requestData = [
        'email' => 'invalid@example.com',
        'password' => 'wrongpassword',
    ];

    $response = $this->postJson('/api/login', $requestData);

    $response->assertStatus(422);
});
