<?php

/** @var \App\Core\Router $router */

$router->get('/', 'HomeController@index');

// Guests only
$router->group(['middleware' => ['guest']], function ($router) {
    $router->get('/login', 'AuthController@showLogin');
    $router->post('/login', 'AuthController@login');
    $router->get('/register', 'AuthController@showRegister');
    $router->post('/register', 'AuthController@register');
});

// Public leaderboard & player profiles
$router->get('/leaderboard', 'LeaderboardController@index');
$router->get('/players/{username}', 'ProfileController@show');

// Signed-in players
$router->group(['middleware' => ['auth']], function ($router) {
    $router->post('/logout', 'AuthController@logout');

    $router->get('/dashboard', 'DashboardController@index');

    $router->get('/play', 'GameController@index');
    $router->get('/game', 'GameController@index');
    $router->get('/game/result/{id}', 'GameController@show');

    $router->get('/profile', 'ProfileController@index');
    $router->get('/achievements', 'AchievementController@index');
    $router->get('/history', 'HistoryController@index');
    $router->get('/statistics', 'StatisticsController@index');

    $router->get('/settings', 'SettingsController@index');
    $router->post('/settings', 'SettingsController@update');
});

// Administration
$router->group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function ($router) {
    $router->get('/', 'AdminController@index');

    $router->get('/players', 'AdminController@players');
    $router->get('/players/{id}', 'AdminController@player');
    $router->post('/players/{id}/status', 'AdminController@toggleStatus');
    $router->post('/players/{id}/role', 'AdminController@changeRole');

    $router->get('/games', 'AdminController@games');
    $router->post('/games/{id}/delete', 'AdminController@deleteGame');

    $router->get('/badges', 'AdminController@badges');
    $router->post('/badges', 'AdminController@saveBadge');
    $router->post('/badges/{id}/delete', 'AdminController@deleteBadge');

    $router->get('/leaderboard', 'AdminController@leaderboard');
    $router->post('/leaderboard/recalculate', 'AdminController@recalculate');
});
