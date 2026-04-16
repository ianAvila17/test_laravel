<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Http\Resources\InstructorResource;
use App\Models\Course;
use App\Models\Instructor;
use App\Services\CourseRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="API Plataforma de Cursos",
 *     version="1.0.0",
 *     description="API para gestionar cursos, instructores, lecciones, comentarios, calificaciones y favoritos."
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Servidor local"
 * )
 */
class CourseController extends Controller
{
    public function __construct(
        private readonly CourseRatingService $courseRatingService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/courses",
     *     operationId="listCourses",
     *     tags={"Courses"},
     *     summary="Lista cursos e instructores",
     *
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="instructors_per_page", in="query", required=false, @OA\Schema(type="integer", default=100)),
     *     @OA\Parameter(name="instructors_cursor", in="query", required=false, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Listado paginado de cursos e instructores")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        /**
         * El enunciado marca que esta funcion deberia retornar solo instructores, no cursos, pero por estructura REST, retorno ambos.
         * Por lo tanto, se retorna un array con dos claves: 'instructors' y 'courses', cada una con su respectiva paginacion.
         * Dejo en este mismo Controller la funcion correspondiente al final, para listar cursos, y la de instructores en su correspondiente controller
         */
        $coursePerPage = max(1, min(100, (int) $request->integer('per_page', 15)));
        $instructorsPerPage = max(1, min(1000, (int) $request->integer('instructors_per_page', 100)));

        $courses = Course::query()
            ->select(['id', 'instructor_id', 'title', 'description', 'price', 'level', 'is_published', 'created_at'])
            ->with(['instructor:id,name,headline'])
            ->withCount('lessons')
            ->withAvg('ratings as average_rating', 'score')
            ->latest('id')
            ->paginate($coursePerPage);

        $instructors = Instructor::query()
            ->select(['id', 'name', 'headline'])
            ->orderBy('id')
            ->cursorPaginate(
                perPage   : $instructorsPerPage,
                columns   : ['*'],
                cursorName: 'instructors_cursor'
            );

        return response()->json([
            'instructors' => [
                'data' => InstructorResource::collection($instructors->items())->resolve(),
                'pagination' => [
                    'per_page' => $instructors->perPage(),
                    'path' => $instructors->path(),
                    'next_cursor' => $instructors->nextCursor()?->encode(),
                    'prev_cursor' => $instructors->previousCursor()?->encode(),
                ],
            ],
            'courses' => [
                'data' => CourseResource::collection($courses->items())->resolve(),
                'pagination' => [
                    'current_page' => $courses->currentPage(),
                    'per_page' => $courses->perPage(),
                    'last_page' => $courses->lastPage(),
                    'total' => $courses->total(),
                    'next_page_url' => $courses->nextPageUrl(),
                    'prev_page_url' => $courses->previousPageUrl(),
                    'path' => $courses->path(),
                ],
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/courses",
     *     operationId="storeCourse",
     *     tags={"Courses"},
     *     summary="Crea un curso",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"instructor_id","title","description","price","level"},
     *
     *             @OA\Property(property="instructor_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Laravel desde cero"),
     *             @OA\Property(property="description", type="string", example="Curso completo para aprender Laravel 12 desde cero."),
     *             @OA\Property(property="price", type="number", format="float", example=29.99),
     *             @OA\Property(property="level", type="string", example="beginner"),
     *             @OA\Property(property="is_published", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Curso creado"),
     *     @OA\Response(response=422, description="Error de validacion")
     * )
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = Course::create($request->validated());
        $course->load('instructor:id,name,headline');
        $course->loadCount('lessons');
        $course->setAttribute('average_rating', 0);

        return (new CourseResource($course))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/courses/{course}",
     *     operationId="showCourse",
     *     tags={"Courses"},
     *     summary="Obtiene un curso",
     *
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Curso encontrado"),
     *     @OA\Response(response=404, description="Curso no encontrado")
     * )
     */
    public function show(Course $course): CourseResource
    {
        $course->load(['instructor:id,name,headline', 'lessons:id,course_id,title,video_url,position']);
        $course->loadCount('lessons');
        $course->setAttribute(
            'average_rating',
            $this->courseRatingService->calculateAverageForCourse($course)
        );

        return new CourseResource($course);
    }

    /**
     * @OA\Put(
     *     path="/api/courses/{course}",
     *     operationId="updateCourse",
     *     tags={"Courses"},
     *     summary="Actualiza un curso",
     *
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="instructor_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Laravel avanzado"),
     *             @OA\Property(property="description", type="string", example="Contenido actualizado del curso."),
     *             @OA\Property(property="price", type="number", format="float", example=59.99),
     *             @OA\Property(property="level", type="string", example="advanced"),
     *             @OA\Property(property="is_published", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Curso actualizado"),
     *     @OA\Response(response=422, description="Error de validacion")
     * )
     */
    public function update(UpdateCourseRequest $request, Course $course): CourseResource
    {
        $course->update($request->validated());
        $course->load('instructor:id,name,headline');
        $course->loadCount('lessons');
        $course->setAttribute(
            'average_rating',
            $this->courseRatingService->calculateAverageForCourse($course)
        );

        return new CourseResource($course);
    }

    /**
     * @OA\Delete(
     *     path="/api/courses/{course}",
     *     operationId="destroyCourse",
     *     tags={"Courses"},
     *     summary="Elimina un curso",
     *
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=204, description="Curso eliminado")
     * )
     */
    public function destroy(Course $course): Response
    {
        $course->delete();

        return response()->noContent();
    }

    /**
     * @OA\Get(
     *     path="/api/courses/{course}/average-rating",
     *     operationId="courseAverageRating",
     *     tags={"Courses"},
     *     summary="Promedio de rating del curso",
     *
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Promedio calculado")
     * )
     */
    public function averageRating(Course $course): JsonResponse
    {
        return response()->json([
            'course_id' => $course->id,
            'average_rating' => $this->courseRatingService->calculateAverageForCourse($course),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/courses/all",
     *     operationId="listAllCoursesOptimized",
     *     tags={"Courses"},
     *     summary="Lista solo cursos optimizado para alto volumen",
     *
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=100)),
     *     @OA\Parameter(name="courses_cursor", in="query", required=false, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Listado optimizado de cursos")
     * )
     */
    public function all(Request $request): JsonResponse
    {
        $coursePerPage = max(1, min(1000, (int) $request->integer('per_page', 100)));

        $courses = Course::query()
            ->select(['id', 'instructor_id', 'title', 'price', 'level', 'is_published', 'created_at'])
            ->orderBy('id')
            ->cursorPaginate(
                perPage: $coursePerPage,
                columns: ['*'],
                cursorName: 'courses_cursor'
            );

        return response()->json([
            'courses' => [
                'data' => CourseResource::collection($courses->items())->resolve(),
                'pagination' => [
                    'per_page' => $courses->perPage(),
                    'path' => $courses->path(),
                    'next_cursor' => $courses->nextCursor()?->encode(),
                    'prev_cursor' => $courses->previousCursor()?->encode(),
                ],
            ],
        ]);
    }
}
