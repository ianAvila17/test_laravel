<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_users(): void
    {
        User::factory()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        $response = $this->getJson('/api/users');

        $response->assertOk()
            ->assertJsonPath('data.0.email', 'alice@example.com');
    }

    #[Test]
    public function it_creates_a_user(): void
    {
        $payload = [
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/users', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'bob@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'bob@example.com',
            'name' => 'Bob',
        ]);
    }

    #[Test]
    public function it_updates_a_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Carlos',
            'email' => 'carlos@example.com',
        ]);

        $response = $this->putJson("/api/users/{$user->id}", [
            'name' => 'Carlos Updated',
            'email' => 'carlos-updated@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Carlos Updated');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Carlos Updated',
            'email' => 'carlos-updated@example.com',
        ]);
    }

    #[Test]
    public function it_deletes_a_user(): void
    {
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/users/{$user->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }
}
