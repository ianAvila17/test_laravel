<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstructorRequest;
use App\Http\Requests\UpdateInstructorRequest;
use App\Http\Resources\InstructorResource;
use App\Models\Instructor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class InstructorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/instructors",
     *     operationId="listInstructors",
     *     tags={"Instructors"},
     *     summary="Lista instructores",
     *
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="instructors_cursor", in="query", required=false, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Listado de instructores")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(1000, (int) $request->integer('per_page', 100)));

        $instructors = Instructor::query()
            ->select(['id', 'name', 'headline', 'bio', 'created_at'])
            ->withCount('courses')
            ->orderBy('id')
            ->cursorPaginate(
                perPage: $perPage,
                columns: ['*'],
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
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/instructors",
     *     operationId="storeInstructor",
     *     tags={"Instructors"},
     *     summary="Crea un instructor",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(property="name", type="string", example="Ana Martinez"),
     *             @OA\Property(property="headline", type="string", example="Senior Backend Engineer"),
     *             @OA\Property(property="bio", type="string", example="Instructora con experiencia en Laravel y arquitectura de software.")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Instructor creado"),
     *     @OA\Response(response=422, description="Error de validacion")
     * )
     */
    public function store(StoreInstructorRequest $request): JsonResponse
    {
        $instructor = Instructor::create($request->validated());
        $instructor->loadCount('courses');

        return (new InstructorResource($instructor))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/instructors/{instructor}",
     *     operationId="showInstructor",
     *     tags={"Instructors"},
     *     summary="Obtiene un instructor",
     *
     *     @OA\Parameter(name="instructor", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Instructor encontrado")
     * )
     */
    public function show(Instructor $instructor): InstructorResource
    {
        $instructor->loadCount('courses');

        return new InstructorResource($instructor);
    }

    /**
     * @OA\Put(
     *     path="/api/instructors/{instructor}",
     *     operationId="updateInstructor",
     *     tags={"Instructors"},
     *     summary="Actualiza un instructor",
     *
     *     @OA\Parameter(name="instructor", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string", example="Ana Martinez Updated"),
     *             @OA\Property(property="headline", type="string", example="Lead Backend Engineer"),
     *             @OA\Property(property="bio", type="string", example="Actualizacion del perfil del instructor.")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Instructor actualizado"),
     *     @OA\Response(response=422, description="Error de validacion")
     * )
     */
    public function update(UpdateInstructorRequest $request, Instructor $instructor): InstructorResource
    {
        $instructor->update($request->validated());
        $instructor->loadCount('courses');

        return new InstructorResource($instructor);
    }

    /**
     * @OA\Delete(
     *     path="/api/instructors/{instructor}",
     *     operationId="destroyInstructor",
     *     tags={"Instructors"},
     *     summary="Elimina un instructor",
     *
     *     @OA\Parameter(name="instructor", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=204, description="Instructor eliminado")
     * )
     */
    public function destroy(Instructor $instructor): Response
    {
        $instructor->delete();

        return response()->noContent();
    }
}
