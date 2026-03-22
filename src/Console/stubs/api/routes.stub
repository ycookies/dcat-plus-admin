<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

# 无授权可访问的路由
Route::post('oauth/login', 'AuthController@login');
Route::post('oauth/logout', 'AuthController@logout');

# 需要授权的路由组
Route::group(['middleware' => ['admin.apiAuth']], function (Router $router) {
    $router->apiResource('admin-user', UserController::class);
    # 菜单
    $router->apiResource('menu', MenuController::class);
    $router->patch('/menu-batchUpdate','MenuController@batchUpdate');
    $router->post('/menu-batchDestroy','MenuController@batchDelete');
    $router->get('/menu-downImportTplFile','MenuController@downImportTplFile');
    $router->post('/menu-import','MenuController@import');
    $router->get('/menu-export','MenuController@export');
    $router->get('/menu-field','MenuController@field');

    // $router->apiResource('permission', PermissionController::class);
    // $router->apiResource('settings', SettingsController::class);
    // $router->apiResource('role', RoleController::class);
});
