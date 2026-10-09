<?php

use App\Models\User;

function tokenFor(User $user): string
{
    return $user->createToken('api')->plainTextToken;
}

describe('register', function () {
    it('creates a user and returns a token', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'Adli',
            'email' => 'Adli@Example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'name', 'email'], 'token'])
            ->assertJsonPath('data.email', 'adli@example.com')
            ->assertJsonMissingPath('data.password');
        $this->assertDatabaseHas('users', ['email' => 'adli@example.com']);
    });

    it('rejects invalid input', function () {
        $this->postJson('/api/register', [
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
    });

    it('rejects an email taken by another user, ignoring case', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Someone',
            'email' => 'TAKEN@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
    });
});

describe('login', function () {
    it('returns a token for valid credentials', function () {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->postJson('/api/login', ['email' => strtoupper($user->email), 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonStructure(['token']);
    });

    it('rejects missing fields', function () {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    });

    it('rejects a wrong password', function () {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong-pass'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });
});

describe('logout', function () {
    it('revokes only the current token', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $token = tokenFor($user);
        tokenFor($other);

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        expect($user->tokens()->count())->toBe(0)
            ->and($other->tokens()->count())->toBe(1);
    });

    it('requires a token', function () {
        $this->postJson('/api/logout')->assertUnauthorized();
    });
});

describe('me', function () {
    it('returns the token owner, not another user', function () {
        $user = User::factory()->create();
        User::factory()->create();

        $this->withToken(tokenFor($user))->getJson('/api/me')
            ->assertOk()
            ->assertExactJson(['data' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]]);
    });

    it('returns 401 without a token, even for a non-JSON request', function () {
        $this->get('/api/me')->assertUnauthorized();
    });

    it('returns 401 for a revoked token', function () {
        $user = User::factory()->create();
        $token = tokenFor($user);
        $user->tokens()->delete();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    });
});
