<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InteractionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_and_unmarks_a_course_as_favorite(): void
    {
        $user = User::factory()->create();
        $course = $this->createCourse();

        $favoriteResponse = $this->postJson("/api/users/{$user->id}/favorite-courses/{$course->id}");
        $favoriteResponse->assertCreated();

        $this->assertDatabaseHas('course_user_favorites', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $unfavoriteResponse = $this->deleteJson("/api/users/{$user->id}/favorite-courses/{$course->id}");
        $unfavoriteResponse->assertNoContent();

        $this->assertDatabaseMissing('course_user_favorites', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
    }

    #[Test]
    public function it_creates_a_comment_for_course_and_instructor(): void
    {
        $user = User::factory()->create();
        $course = $this->createCourse();
        $instructor = $course->instructor;

        $courseResponse = $this->postJson("/api/courses/{$course->id}/comments", [
            'user_id' => $user->id,
            'body' => 'Excelente curso de Laravel.',
        ]);

        $courseResponse->assertCreated();

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'commentable_type' => Course::class,
            'commentable_id' => $course->id,
        ]);

        $instructorResponse = $this->postJson("/api/instructors/{$instructor->id}/comments", [
            'user_id' => $user->id,
            'body' => 'Muy buen instructor.',
        ]);

        $instructorResponse->assertCreated();

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'commentable_type' => Instructor::class,
            'commentable_id' => $instructor->id,
        ]);
    }

    #[Test]
    public function it_creates_or_updates_rating_for_course_and_instructor(): void
    {
        $user = User::factory()->create();
        $course = $this->createCourse();
        $instructor = $course->instructor;

        $firstCourseRating = $this->postJson("/api/courses/{$course->id}/ratings", [
            'user_id' => $user->id,
            'score' => 5,
            'review' => 'Muy completo',
        ]);

        $firstCourseRating->assertOk()
            ->assertJsonPath('average_rating', 5);

        $secondCourseRating = $this->postJson("/api/courses/{$course->id}/ratings", [
            'user_id' => $user->id,
            'score' => 3,
            'review' => 'Actualizacion de reseña',
        ]);

        $secondCourseRating->assertOk()
            ->assertJsonPath('average_rating', 3);

        $this->assertDatabaseCount('ratings', 1);
        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'rateable_type' => Course::class,
            'rateable_id' => $course->id,
            'score' => 3,
        ]);

        $instructorRating = $this->postJson("/api/instructors/{$instructor->id}/ratings", [
            'user_id' => $user->id,
            'score' => 4,
            'review' => 'Buen nivel de explicacion',
        ]);

        $instructorRating->assertOk()
            ->assertJsonPath('average_rating', 4);

        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'rateable_type' => Instructor::class,
            'rateable_id' => $instructor->id,
            'score' => 4,
        ]);
    }

    #[Test]
    public function it_returns_json_404_when_instructor_does_not_exist(): void
    {
        $response = $this->post('/api/instructors/999999/comments', [
            'user_id' => 1,
            'body' => 'Comentario de prueba',
        ]);

        $response->assertNotFound()
            ->assertHeader('content-type', 'application/json')
            ->assertJson([
                'message' => 'Instructor not found.',
            ]);
    }

    #[Test]
    public function it_returns_json_422_for_validation_errors_without_accept_header(): void
    {
        $instructor = Instructor::create([
            'name' => 'Instructor Test',
            'headline' => 'API Trainer',
            'bio' => 'Instructor de pruebas.',
        ]);

        $response = $this->post("/api/instructors/{$instructor->id}/comments", []);

        $response->assertStatus(422)
            ->assertHeader('content-type', 'application/json')
            ->assertJsonStructure([
                'message',
                'errors' => ['user_id', 'body'],
            ]);
    }

    private function createCourse(): Course
    {
        $instructor = Instructor::create([
            'name' => 'Instructor Test',
            'headline' => 'API Trainer',
            'bio' => 'Instructor de pruebas.',
        ]);

        return Course::create([
            'instructor_id' => $instructor->id,
            'title' => 'Curso API Laravel',
            'description' => 'Descripcion suficientemente larga para validacion.',
            'price' => 19.99,
            'level' => 'beginner',
            'is_published' => true,
        ]);
    }
}
