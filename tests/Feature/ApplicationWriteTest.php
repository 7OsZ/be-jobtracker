<?php

use App\Models\Application;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('store', function () {
    it('creates an application that defaults to wishlist', function () {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/applications', ['company' => 'Acme', 'position' => 'Backend Engineer'])
            ->assertCreated()
            ->assertJsonPath('data.company', 'Acme')
            ->assertJsonPath('data.status', 'wishlist')
            ->assertJsonPath('data.applied_at', null);

        expect($this->user->applications()->count())->toBe(1);
    });

    it('stamps applied_at when created as applied without a date', function () {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/applications', ['company' => 'Acme', 'position' => 'Dev', 'status' => 'applied'])
            ->assertCreated()
            ->assertJsonPath('data.applied_at', today()->toDateString());
    });

    it('rejects invalid input, including an unknown status', function () {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/applications', [
            'company' => str_repeat('a', 121),
            'url' => 'not a url',
            'status' => 'ghosted',
            'applied_at' => '09/10/2026',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['company', 'position', 'url', 'status', 'applied_at']);
    });

    it('requires a token', function () {
        $this->postJson('/api/applications', ['company' => 'Acme', 'position' => 'Dev'])->assertUnauthorized();
    });
});

describe('update', function () {
    it('updates only the sent fields', function () {
        $application = Application::factory()->for($this->user)->create(['company' => 'Acme', 'notes' => 'keep']);
        Sanctum::actingAs($this->user);

        $this->patchJson("/api/applications/{$application->id}", ['status' => 'interview'])
            ->assertOk()
            ->assertJsonPath('data.status', 'interview')
            ->assertJsonPath('data.company', 'Acme')
            ->assertJsonPath('data.notes', 'keep');
    });

    it('stamps applied_at when moved to applied without a date', function () {
        $application = Application::factory()->for($this->user)->create();
        Sanctum::actingAs($this->user);

        $this->patchJson("/api/applications/{$application->id}", ['status' => 'applied'])
            ->assertOk()
            ->assertJsonPath('data.applied_at', today()->toDateString());
    });

    it('keeps an existing applied_at when moved to applied', function () {
        $application = Application::factory()->for($this->user)->create(['applied_at' => '2026-09-01']);
        Sanctum::actingAs($this->user);

        $this->patchJson("/api/applications/{$application->id}", ['status' => 'applied'])
            ->assertJsonPath('data.applied_at', '2026-09-01');
    });

    it('rejects an invalid status and empty required fields', function () {
        $application = Application::factory()->for($this->user)->create();
        Sanctum::actingAs($this->user);

        $this->patchJson("/api/applications/{$application->id}", ['status' => 'ghosted', 'company' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'company']);
    });

    it('returns 404 for another user\'s application and leaves it unchanged', function () {
        $foreign = Application::factory()->create(['company' => 'Theirs']);
        Sanctum::actingAs($this->user);

        $this->patchJson("/api/applications/{$foreign->id}", ['company' => 'Mine'])->assertNotFound();

        expect($foreign->fresh()->company)->toBe('Theirs');
    });
});

describe('destroy', function () {
    it('deletes an own application', function () {
        $application = Application::factory()->for($this->user)->create();
        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/applications/{$application->id}")->assertNoContent();

        $this->assertModelMissing($application);
    });

    it('returns 404 for another user\'s application and keeps it', function () {
        $foreign = Application::factory()->create();
        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/applications/{$foreign->id}")->assertNotFound();

        $this->assertModelExists($foreign);
    });
});
