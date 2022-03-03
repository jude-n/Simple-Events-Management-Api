<?php

use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::group([
    'prefix' => 'v1',
    'as' => 'api.',
//    'namespace' => 'Api\V1\Admin',
//    'middleware' => ['auth:api']
], function () {
    Route::apiResource('users', UsersController::class);
    Route::post('/users/activate',[UsersController::class,'activateUser']);
    Route::post('/users/forgot/password',[UsersController::class,'forgotPassword']);
    Route::post('/users/reset/password',[UsersController::class,'resetPassword']);
    Route::apiResource('roles', RolesController::class);
    Route::apiResource('permissions', PermissionsController::class);
});
