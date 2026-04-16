<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/users",
     *     operationId="listUsers",
     *     tags={"Users"},
     *     summary="Lista usuarios",
     *
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *
     *     @OA\Response(response=200, description="Listado de usuarios")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->integer('per_page', 15)));

        $users = User::query()
            ->select(['id', 'name', 'email', 'created_at'])
            ->withCount(['favoriteCourses', 'comments', 'ratings'])
            ->latest('id')
            ->paginate($perPage);

        return UserResource::collection($users)->response();
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     operationId="storeUser",
     *     tags={"Users"},
     *     summary="Crea un usuario",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *
     *             @OA\Property(property="name", type="string", example="Carlos Perez"),
     *             @OA\Property(property="email", type="string", format="email", example="carlos@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Usuario creado"),
     *     @OA\Response(response=422, description="Error de validacion")
     * )
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        $user->loadCount(['favoriteCourses', 'comments', 'ratings']);

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{user}",
     *     operationId="showUser",
     *     tags={"Users"},
     *     summary="Obtiene un usuario",
     *
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Usuario encontrado")
     * )
     */
    public function show(User $user): UserResource
    {
        $user->loadCount(['favoriteCourses', 'comments', 'ratings']);

        return new UserResource($user);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{user}",
     *     operationId="updateUser",
     *     tags={"Users"},
     *     summary="Actualiza un usuario",
     *
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string", example="Carlos Perez Updated"),
     *             @OA\Property(property="email", type="string", format="email", example="carlos.updated@example.com"),
     *             @OA\Property(property="password", type="string", example="newpassword123")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Usuario actualizado"),
     *     @OA\Response(response=422, description="Error de validacion")
     * )
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user->update($request->validated());
        $user->loadCount(['favoriteCourses', 'comments', 'ratings']);

        return new UserResource($user);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{user}",
     *     operationId="destroyUser",
     *     tags={"Users"},
     *     summary="Elimina un usuario",
     *
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=204, description="Usuario eliminado")
     * )
     */
    public function destroy(User $user): Response
    {
        $user->delete();

        return response()->noContent();
    }
}
