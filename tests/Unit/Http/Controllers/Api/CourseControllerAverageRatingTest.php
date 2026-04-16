<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\CourseController;
use App\Models\Course;
use App\Services\CourseRatingService;
use Illuminate\Http\JsonResponse;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CourseControllerAverageRatingTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_expected_average_rating_payload(): void
    {
        $course = new Course;
        $course->id = 42;

        $service = Mockery::mock(CourseRatingService::class);
        $service->shouldReceive('calculateAverageForCourse')
            ->once()
            ->with($course)
            ->andReturn(4.75);

        $controller = new CourseController($service);
        $response = $controller->averageRating($course);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(
            [
                'course_id' => 42,
                'average_rating' => 4.75,
            ],
            $response->getData(true)
        );
    }
}
