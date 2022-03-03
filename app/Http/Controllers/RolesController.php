<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Http\Services\ApiResponseService;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Laratrust\Helper;

class RolesController extends Controller
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
        return $this->apiResponseService->respondWithResourceCollection(RoleResource::collection(Role::all()));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'display_name' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $role = Role::create($data);
        $role->syncPermissions($request->get('permissions') ?? []);

        return $this->apiResponseService->respondWithResource(new RoleResource($role), 'User created', 201);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Role $role
     * @return JsonResponse
     */
    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'display_name' => 'nullable|string',
            'description' => 'nullable|string',
        ]);
//        dd($request);
        $updatedRole = $role->update($data);
        $role->syncPermissions($request->get('permissions') ?? []);
        if($updatedRole)
            return $this->apiResponseService->respondSuccess('Role updated');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Role $role
     * @return JsonResponse
     */
    public function destroy(Role $role)
    {
        $usersAssignedToRole = DB::table(Config::get('laratrust.tables.role_user'))
            ->where(Config::get('laratrust.foreign_keys.role'), $role->id)
            ->count();

        if ($usersAssignedToRole > 0) {
            return $this->apiResponseService->respondError('Role is attached to one or more users. It can not be deleted');

        }
        $role->delete();
        return $this->apiResponseService->respondSuccess('Role deleted');
    }
}
