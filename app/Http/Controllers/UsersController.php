<?php

namespace App\Http\Controllers;

use App\Http\ApiResponseService;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UsersController extends Controller
{
    /**
     * @param ApiResponseService $apiResponseService
     */
    public function __construct(ApiResponseService $apiResponseService)
    {
        $this->apiResponseService = $apiResponseService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        return $this->apiResponseService->respondWithResourceCollection(UserResource::collection(User::all()));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(StoreUserRequest $request)
    {
        $userData = $request->getSanitized();
        $storedUser = User::create($userData);
        if($storedUser)
            $this->apiResponseService->respondCreated($storedUser);
    }

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user)
    {
        return $this->apiResponseService->respondWithResource(new UserResource($user),'User found');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $userDataToUpdate = $request->getSanitized();
        $userUpdated = $user->update($userDataToUpdate);
        if($userUpdated)
            return $this->apiResponseService->respondSuccess('User updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user)
    {
        $userDeleted = $user->delete();
        if($userDeleted)
            return $this->apiResponseService->respondSuccess('User deleted');
    }
}
