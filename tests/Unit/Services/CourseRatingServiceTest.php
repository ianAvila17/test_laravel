<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Services\CourseRatingService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CourseRatingServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_calculates_average_rating_and_rounds_to_two_decimals(): void
    {
        $relation = Mockery::mock(MorphMany::class);
        $relation->shouldReceive('avg')
            ->once()
            ->with('score')
            ->andReturn(4.256);

        $course = Mockery::mock(Course::class);
        $course->shouldReceive('ratings')
            ->once()
            ->andReturn($relation);

        $service = new CourseRatingService;

        $average = $service->calculateAverageForCourse($course);

        $this->assertSame(4.26, $average);
    }

    #[Test]
    public function it_returns_zero_when_course_has_no_ratings(): void
    {
        $relation = Mockery::mock(MorphMany::class);
        $relation->shouldReceive('avg')
            ->once()
            ->with('score')
            ->andReturn(null);

        $course = Mockery::mock(Course::class);
        $course->shouldReceive('ratings')
            ->once()
            ->andReturn($relation);

        $service = new CourseRatingService;

        $average = $service->calculateAverageForCourse($course);

        $this->assertSame(0.0, $average);
    }
}
