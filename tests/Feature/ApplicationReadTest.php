<?php

use App\Models\Application;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('index', function () {
    it('lists only own applications, newest update first', function () {
        $old = Application::factory()->for($this->user)->create(['updated_at' => now()->subDay()]);
        $new = Application::factory()->for($this->user)->create(['updated_at' => now()]);
        Application::factory()->create();
        Sanctum::actingAs($this->user);

        $this->getJson('/api/applications')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $new->id)
            ->assertJsonPath('data.1.id', $old->id)
            ->assertJsonStructure(['data' => [['id', 'company', 'position', 'url', 'location', 'status', 'applied_at', 'notes', 'created_at', 'updated_at']]]);
    });

    it('requires a token', function () {
        $this->getJson('/api/applications')->assertUnauthorized();
    });
});

describe('show', function () {
    it('returns an own application', function () {
        $application = Application::factory()->for($this->user)->create(['applied_at' => '2026-10-01']);
        Sanctum::actingAs($this->user);

        $this->getJson("/api/applications/{$application->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $application->id)
            ->assertJsonPath('data.status', 'wishlist')
            ->assertJsonPath('data.applied_at', '2026-10-01');
    });

    it('returns 404 for another user\'s application', function () {
        $foreign = Application::factory()->create();
        Sanctum::actingAs($this->user);

        $this->getJson("/api/applications/{$foreign->id}")->assertNotFound();
    });

    it('returns 404 for a missing or non-numeric id', function () {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/applications/999999')->assertNotFound();
        $this->getJson('/api/applications/abc')->assertNotFound();
    });
});
