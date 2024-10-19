<?php
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a user for testing
    $this->user = User::factory()->create([
        'email' => 'ramyelsabahy95@gmail.com',
        'password' => bcrypt('password'),
    ]);
    Artisan::call('passport:client', ['--personal' => true]);
});

it('can log in a user and returns the expected response', function () {
    // Set up the request data
    $requestData = [
        'email' => 'ramyelsabahy95@gmail.com',
        'password' => 'password',
    ];

    // Send a POST request to the login endpoint
    $response = $this->postJson('/api/login', $requestData);

    // Assert that the response status is 200 OK
    $response->assertStatus(200);

    // Assert that the response structure is as expected
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

    // Assert the content of the response
    $response->assertJson([
        'data' => [
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
        ],
    ]);

    // Optionally, you can assert that the token is returned
    expect($response->json('data.token'))->toBeString();
});

it('returns an error when credentials are invalid', function () {
    // Set up the request data with invalid credentials
    $requestData = [
        'email' => 'invalid@example.com',
        'password' => 'wrongpassword',
    ];

    // Send a POST request to the login endpoint
    $response = $this->postJson('/api/login', $requestData);

    // Assert that the response status is 422 Unprocessable entity
    $response->assertStatus(422);
});
