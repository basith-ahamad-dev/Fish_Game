<?php

/** @var \App\Core\Router $router */

// Internal JSON endpoints consumed by public/assets/js via fetch().

$router->get('/api/stats', 'HomeController@stats');
$router->get('/api/leaderboard', 'LeaderboardController@data');

$router->group(['middleware' => ['auth']], function ($router) {
    // Game flow: start → round → answer → … → result (all scoring happens server-side)
    $router->post('/game/start', 'GameController@start');
    $router->post('/game/round', 'GameController@round');
    $router->post('/game/answer', 'GameController@answer');
    $router->post('/game/abandon', 'GameController@abandon');
    $router->post('/game/result', 'GameController@result');

    $router->get('/api/statistics', 'StatisticsController@data');
});
