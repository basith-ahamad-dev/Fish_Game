<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Response;
use App\Models\Game;
use App\Models\User;
use App\Services\AuthService;
use App\Services\BadgeService;
use App\Services\LeaderboardService;
use App\Services\LevelService;
use App\Services\StatisticsService;

class ProfileController extends Controller
{
    /** The signed-in player's own profile. */
    public function index(): Response
    {
        return $this->render($this->user());
    }

    /** Public profile: /players/{username} */
    public function show(string $username): Response
    {
        $player = (new User())->findByUsername($username);
        if (!$player || $player['status'] !== 'active') {
            throw new HttpException(404, 'That angler could not be found.');
        }

        return $this->render($player);
    }

    private function render(array $player): Response
    {
        $levels = new LevelService();
        $badges = array_values(array_filter(
            (new BadgeService())->progressFor($player),
            fn ($b) => $b['unlocked']
        ));

        return $this->view('profile/index', [
            'title'    => $player['username'],
            'player'   => $player,
            'isOwn'    => AuthService::id() === (int) $player['id'],
            'progress' => $levels->getXPProgress((int) $player['xp']),
            'levelTitle' => $levels->titleFor((int) $player['level']),
            'rank'     => (new LeaderboardService())->globalRank($player),
            'winRate'  => (new StatisticsService())->winRate($player),
            'badges'   => $badges,
            'recent'   => (new Game())->recentForUser((int) $player['id'], 6),
        ], AuthService::user() ? 'dashboard' : 'main');
    }
}
