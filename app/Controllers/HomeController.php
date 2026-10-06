<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\LeaderboardService;
use App\Services\StatisticsService;

class HomeController extends Controller
{
    public function index(): Response
    {
        return $this->view('home/index', [
            'title'   => 'Catch. Compete. Conquer.',
            'stats'   => (new StatisticsService())->platform(),
            'leaders' => array_slice((new LeaderboardService())->board('global', null, 5)['rows'], 0, 5),
        ], 'main');
    }

    /** GET /api/stats — live counters for the landing page. */
    public function stats(): Response
    {
        return $this->json((new StatisticsService())->platform());
    }
}
