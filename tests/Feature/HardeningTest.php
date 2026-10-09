<?php

use App\Models\Application;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

dataset('protected routes', [
    'logout' => ['post', '/api/logout'],
    'me' => ['get', '/api/me'],
    'index' => ['get', '/api/applications'],
    'store' => ['post', '/api/applications'],
    'show' => ['get', '/api/applications/1'],
    'update' => ['patch', '/api/applications/1'],
    'destroy' => ['delete', '/api/applications/1'],
]);

dataset('id routes', [
    'show' => ['get'],
    'update' => ['patch'],
    'destroy' => ['delete'],
]);

it('returns 401 without a token', function (string $method, string $uri) {
    $this->json($method, $uri)->assertUnauthorized();
})->with('protected routes');

it('returns 404 for another user\'s application', function (string $method) {
    $foreign = Application::factory()->create(['company' => 'Theirs']);
    Sanctum::actingAs(User::factory()->create());

    $this->json($method, "/api/applications/{$foreign->id}", ['company' => 'Mine'])->assertNotFound();

    expect($foreign->fresh()?->company)->toBe('Theirs');
})->with('id routes');

it('ignores a user_id sent on store', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/applications', ['company' => 'Acme', 'position' => 'Dev', 'user_id' => $other->id])
        ->assertCreated();

    expect($user->applications()->count())->toBe(1)
        ->and($other->applications()->count())->toBe(0);
});

it('ignores a user_id sent on update', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $application = Application::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->patchJson("/api/applications/{$application->id}", ['user_id' => $other->id])->assertOk();

    expect($application->fresh()->user_id)->toBe($user->id);
});

describe('cors', function () {
    it('allows the frontend origin', function () {
        $this->withHeaders([
            'Origin' => config('cors.allowed_origins')[0],
            'Access-Control-Request-Method' => 'PATCH',
        ])->options('/api/applications/1')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    });

    it('does not allow another origin', function () {
        $response = $this->withHeaders([
            'Origin' => 'http://evil.test',
            'Access-Control-Request-Method' => 'PATCH',
        ])->options('/api/applications/1');

        // Single allowed origin is always echoed; the browser blocks the mismatch.
        expect($response->headers->get('Access-Control-Allow-Origin'))->not->toBe('http://evil.test');
    });
});
