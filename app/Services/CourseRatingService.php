<?php

namespace App\Services;

use App\Models\Course;

class CourseRatingService
{
    public function calculateAverageForCourse(Course $course): float
    {
        return round((float) $course->ratings()->avg('score'), 2);
    }
}
