<?php

namespace App\Http\Controllers;

use App\Http\Resources\PermissionResource;
use App\Http\Services\ApiResponseService;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionsController extends Controller
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
        return $this->apiResponseService->respondWithResourceCollection(PermissionResource::collection(Permission::all()));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'display_name' => 'nullable|string',
            'description' => 'nullable|string',
        ]);
        $permission = Permission::create($data);
        if($permission)
            return $this->apiResponseService->respondWithResource(new PermissionResource($permission), 'Permission created', 201);


    }

    /**
     * Display the specified resource.
     *
     * @param Permission $permission
     * @return JsonResponse
     */
    public function show(Permission $permission)
    {
        return $this->apiResponseService->respondWithResource(new PermissionResource($permission));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param Permission $permission
     * @return JsonResponse
     */
    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'display_name' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $permission->update($data);
        return $this->apiResponseService->respondSuccess('Permission updated');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Permission $permission
     * @return JsonResponse
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();
        return $this->apiResponseService->respondSuccess('Permission deleted');

    }
}
