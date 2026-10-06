<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\LeaderboardService;

class LeaderboardController extends Controller
{
    public function index(): Response
    {
        $service = new LeaderboardService();
        $board = $service->board((string) $this->request->query('tab', 'global'), AuthService::id());

        return $this->view('leaderboard/index', [
            'title' => 'Leaderboard',
            'tabs'  => LeaderboardService::TABS,
            'board' => $board,
        ], AuthService::user() ? 'dashboard' : 'main');
    }

    /** GET /api/leaderboard?tab=weekly — rendered rows for tab switching without a reload. */
    public function data(): Response
    {
        $board = (new LeaderboardService())->board((string) $this->request->query('tab', 'global'), AuthService::id());

        return $this->json([
            'tab'  => $board['tab'],
            'note' => $board['note'],
            'html' => \App\Core\View::renderFile('leaderboard/_table', ['board' => $board]),
        ]);
    }
}
