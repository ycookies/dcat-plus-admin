<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

# 无授权可访问的路由
Route::post('uauth/login', 'AuthController@login');
Route::post('uauth/logout', 'AuthController@logout');

# 需要授权的路由组
Route::group(['middleware' => ['member.apiAuth']], function (Router $router) {
    $router->apiResource('member-user', MemberUserController::class);
});
