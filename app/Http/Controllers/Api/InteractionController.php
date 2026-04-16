<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreRatingRequest;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\User;
use App\Services\CourseRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class InteractionController extends Controller
{
    public function __construct(
        private readonly CourseRatingService $courseRatingService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/users/{user}/favorite-courses/{course}",
     *     operationId="favoriteCourse",
     *     tags={"Interactions"},
     *     summary="Marca curso como favorito",
     *
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=201, description="Curso marcado como favorito")
     * )
     */
    public function favoriteCourse(User $user, Course $course): JsonResponse
    {
        $user->favoriteCourses()->syncWithoutDetaching([$course->id]);

        return response()->json([
            'message' => 'Curso marcado como favorito',
            'user_id' => $user->id,
            'course_id' => $course->id,
        ], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{user}/favorite-courses/{course}",
     *     operationId="unfavoriteCourse",
     *     tags={"Interactions"},
     *     summary="Desmarca curso como favorito",
     *
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=204, description="Curso removido de favoritos")
     * )
     */
    public function unfavoriteCourse(User $user, Course $course): Response
    {
        $user->favoriteCourses()->detach($course->id);

        return response()->noContent();
    }

    /**
     * @OA\Post(
     *     path="/api/courses/{course}/comments",
     *     operationId="commentCourse",
     *     tags={"Interactions"},
     *     summary="Comenta un curso",
     *
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"user_id","body"},
     *
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="body", type="string", example="Excelente curso de Laravel.")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Comentario creado")
     * )
     */
    public function commentCourse(StoreCommentRequest $request, Course $course): JsonResponse
    {
        $comment = $course->comments()->create($request->validated());
        $comment->load('user:id,name,email');

        return response()->json([
            'message' => 'Comentario creado para el curso',
            'data' => $comment,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/instructors/{instructor}/comments",
     *     operationId="commentInstructor",
     *     tags={"Interactions"},
     *     summary="Comenta un instructor",
     *
     *     @OA\Parameter(name="instructor", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"user_id","body"},
     *
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="body", type="string", example="Muy buen instructor.")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Comentario creado")
     * )
     */
    public function commentInstructor(StoreCommentRequest $request, Instructor $instructor): JsonResponse
    {
        $comment = $instructor->comments()->create($request->validated());
        $comment->load('user:id,name,email');

        return response()->json([
            'message' => 'Comentario creado para el instructor',
            'data' => $comment,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/courses/{course}/ratings",
     *     operationId="rateCourse",
     *     tags={"Interactions"},
     *     summary="Califica un curso",
     *
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"user_id","score"},
     *
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="score", type="integer", minimum=1, maximum=5, example=5),
     *             @OA\Property(property="review", type="string", example="Muy completo")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Calificacion registrada")
     * )
     */
    public function rateCourse(StoreRatingRequest $request, Course $course): JsonResponse
    {
        $payload = $request->validated();

        $rating = $course->ratings()->updateOrCreate(
            ['user_id' => $payload['user_id']],
            ['score' => $payload['score'], 'review' => $payload['review'] ?? null]
        );

        return response()->json([
            'message' => 'Calificacion registrada para el curso',
            'data' => $rating,
            'average_rating' => $this->courseRatingService->calculateAverageForCourse($course),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/instructors/{instructor}/ratings",
     *     operationId="rateInstructor",
     *     tags={"Interactions"},
     *     summary="Califica un instructor",
     *
     *     @OA\Parameter(name="instructor", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"user_id","score"},
     *
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="score", type="integer", minimum=1, maximum=5, example=4),
     *             @OA\Property(property="review", type="string", example="Buen nivel de explicacion")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Calificacion registrada")
     * )
     */
    public function rateInstructor(StoreRatingRequest $request, Instructor $instructor): JsonResponse
    {
        $payload = $request->validated();

        $rating = $instructor->ratings()->updateOrCreate(
            ['user_id' => $payload['user_id']],
            ['score' => $payload['score'], 'review' => $payload['review'] ?? null]
        );

        return response()->json([
            'message' => 'Calificacion registrada para el instructor',
            'data' => $rating,
            'average_rating' => round((float) $instructor->ratings()->avg('score'), 2),
        ]);
    }
}
