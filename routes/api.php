<?php

use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\InstructorController;
use App\Http\Controllers\Api\InteractionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('courses/all', [CourseController::class, 'all']);
Route::apiResource('courses', CourseController::class);
Route::apiResource('instructors', InstructorController::class);
Route::apiResource('users', UserController::class);

Route::get('courses/{course}/average-rating', [CourseController::class, 'averageRating']);

Route::post('users/{user}/favorite-courses/{course}', [InteractionController::class, 'favoriteCourse']);
Route::delete('users/{user}/favorite-courses/{course}', [InteractionController::class, 'unfavoriteCourse']);

Route::post('courses/{course}/comments', [InteractionController::class, 'commentCourse']);
Route::post('instructors/{instructor}/comments', [InteractionController::class, 'commentInstructor']);

Route::post('courses/{course}/ratings', [InteractionController::class, 'rateCourse']);
Route::post('instructors/{instructor}/ratings', [InteractionController::class, 'rateInstructor']);
