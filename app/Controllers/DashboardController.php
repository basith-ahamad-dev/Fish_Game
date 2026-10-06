<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\LeaderboardService;
use App\Services\StatisticsService;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = $this->user();
        $board = (new LeaderboardService())->board('global', (int) $user['id'], 5);

        return $this->view('dashboard/index', [
            'title'   => 'Dashboard',
            'user'    => $user,
            'data'    => (new StatisticsService())->dashboard($user),
            'leaders' => $board['rows'],
            'me'      => $board['me'],
        ]);
    }
}
