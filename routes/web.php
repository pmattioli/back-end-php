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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'users'], function () use ($router) {
    
    $router->get('/',  ['uses' => 'UsersController@showAllUsers']);
    
    $router->get('find/{term}', ['uses' => 'UsersController@findUsers']);
    
    $router->get('chain/{chainID}', ['uses' => 'UsersController@getUsersFromChain']);
    
    $router->get('chain/{chainID}/disabled', ['uses' => 'UsersController@getDisabledUsersFromChain']);
    
    $router->get('country/{country}', ['uses' => 'UsersController@getUsersFromCountryOutsideChain']);
    
    $router->get('country/{country}/disabled', ['uses' => 'UsersController@getDisabledUsersFromCountryOutsideChain']);
    
    $router->get('info', ['uses' => 'UsersController@getUserInfo']);
    
    $router->post('/', ['uses' => 'UsersController@saveUserDetails']);
    
    $router->put('password', ['uses' => 'UsersController@changePassword']);
    
});

$router->group(['prefix' => 'chains'], function () use ($router) {
    
    $router->put('/',  ['uses' => 'ChainController@updateChainInfo']);
    
    $router->put('updateLogoFilePathPDFSettings',  ['uses' => 'ChainController@updateLogoFilePathPDFSettings']);
        
});

$router->group(['prefix' => 'auth'], function () use ($router) {
    
    $router->post('login',  ['uses' => 'AuthController@login']);
    
});