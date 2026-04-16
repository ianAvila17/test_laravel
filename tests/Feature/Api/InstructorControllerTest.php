<?php

namespace Tests\Feature\Api;

use App\Models\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstructorControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_instructors(): void
    {
        Instructor::create([
            'name' => 'Jane Doe',
            'headline' => 'Backend Expert',
            'bio' => 'Especialista en APIs y arquitectura.',
        ]);

        $response = $this->getJson('/api/instructors');

        $response->assertOk()
            ->assertJsonPath('instructors.data.0.name', 'Jane Doe');
    }

    #[Test]
    public function it_creates_an_instructor(): void
    {
        $payload = [
            'name' => 'John Smith',
            'headline' => 'Laravel Mentor',
            'bio' => 'Instructor senior de Laravel.',
        ];

        $response = $this->postJson('/api/instructors', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'John Smith');

        $this->assertDatabaseHas('instructors', [
            'name' => 'John Smith',
            'headline' => 'Laravel Mentor',
        ]);
    }

    #[Test]
    public function it_updates_an_instructor(): void
    {
        $instructor = Instructor::create([
            'name' => 'Old Name',
            'headline' => 'Old Headline',
            'bio' => 'Old bio',
        ]);

        $response = $this->putJson("/api/instructors/{$instructor->id}", [
            'name' => 'New Name',
            'headline' => 'New Headline',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('instructors', [
            'id' => $instructor->id,
            'name' => 'New Name',
            'headline' => 'New Headline',
        ]);
    }

    #[Test]
    public function it_deletes_an_instructor(): void
    {
        $instructor = Instructor::create([
            'name' => 'To Delete',
            'headline' => 'Soon deleted',
            'bio' => 'Bio',
        ]);

        $response = $this->deleteJson("/api/instructors/{$instructor->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('instructors', [
            'id' => $instructor->id,
        ]);
    }
}
