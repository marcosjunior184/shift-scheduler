<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

// Role Routes with logging middleware
$router->group(['middleware' => App\Http\Middleware\RequestLogger::class, 'prefix' => 'api'], function () use ($router) {
    
    // Role Routes
    $router->group(['prefix' => 'roles'], function () use ($router) {
        $router->get('/', 'RoleController@index');      
        $router->post('/', 'RoleController@store');     // Create role

        $router->group(['prefix' => '{id}'], function () use ($router) {
            $router->put('/', 'RoleController@update'); // Update role
            $router->delete('/', 'RoleController@destroy');
        });
        
    });

    // Staff Routes
    $router->group(['prefix' => 'staff'], function () use ($router) {
        $router->get('/', 'StaffController@index');
        $router->post('/', 'StaffController@store');  // Create staff

        $router->group(['prefix' => '{id}'], function () use ($router) {
            $router->get('/{id}', 'StaffController@show');
            $router->put('/{id}', 'StaffController@update'); // Update staff
            $router->delete('/{id}', 'StaffController@destroy');
            $router->put('/{id}/terminate', 'StaffController@terminate');
        });
    });

    // Schedule Routes
    $router->group(['prefix' => 'schedules'], function () use ($router) {
        $router->get('/', 'ScheduleController@index');
        $router->post('/', 'ScheduleController@store');  // Create schedule
        
        $router->group(['prefix' => '{id}'], function () use ($router) {
            $router->put('/', 'ScheduleController@update');  // Update schedule
            $router->delete('/', 'ScheduleController@destroy');
        });
    });

});