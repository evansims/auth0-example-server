<?php

$router->get('users', ['uses' => 'UsersController@list']);
$router->post('users', ['uses' => 'UsersController@create']);
$router->get('users/{id}', ['uses' => 'UsersController@get']);
$router->put('users/{id}', ['uses' => 'UsersController@update']);
$router->delete('users/{id}', ['uses' => 'UsersController@delete']);
