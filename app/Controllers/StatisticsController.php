<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\StatisticsService;

class StatisticsController extends Controller
{
    public function index(): Response
    {
        return $this->view('statistics/index', [
            'title' => 'Statistics',
            'stats' => (new StatisticsService())->forUser($this->user()),
        ]);
    }

    /** GET /api/statistics — chart data as JSON. */
    public function data(): Response
    {
        $stats = (new StatisticsService())->forUser($this->user());

        return $this->json(['series' => $stats['series'], 'activity' => $stats['activity']]);
    }
}
