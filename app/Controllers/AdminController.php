<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Badge;
use App\Models\Game;
use App\Models\User;
use App\Services\BadgeService;
use App\Services\LeaderboardService;
use App\Services\LevelService;
use App\Services\StatisticsService;

class AdminController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): Response
    {
        return $this->view('admin/index', [
            'title'   => 'Admin',
            'stats'   => (new StatisticsService())->admin(),
            'recent'  => (new Game())->recentAll(8),
            'newest'  => (new User())->newestPlayers(6),
        ]);
    }

    /* ---------------- Players ---------------- */

    public function players(): Response
    {
        $q = trim((string) $this->request->query('q', ''));
        $status = in_array($this->request->query('status'), ['active', 'disabled'], true) ? $this->request->query('status') : null;
        $role = in_array($this->request->query('role'), ['player', 'admin'], true) ? $this->request->query('role') : null;
        $page = max(1, (int) $this->request->query('page', 1));

        [$players, $total] = (new User())->search($q, $status, $role, $page, self::PER_PAGE);

        return $this->view('admin/players', [
            'title'      => 'Players',
            'players'    => $players,
            'filters'    => ['q' => $q, 'status' => $status, 'role' => $role],
            'pagination' => paginate($total, $page, self::PER_PAGE),
        ]);
    }

    public function player(string $id): Response
    {
        $player = $this->findPlayer($id);
        $page = max(1, (int) $this->request->query('page', 1));
        [$games, $total] = (new Game())->paginateForUser((int) $player['id'], null, null, $page, 10);

        return $this->view('admin/player', [
            'title'      => $player['username'],
            'player'     => $player,
            'progress'   => (new LevelService())->getXPProgress((int) $player['xp']),
            'rank'       => (new LeaderboardService())->globalRank($player),
            'games'      => $games,
            'pagination' => paginate($total, $page, 10),
        ]);
    }

    public function toggleStatus(string $id): Response
    {
        $player = $this->findPlayer($id);
        $this->guardSelf($player, 'You cannot disable your own account.');

        $status = $player['status'] === 'active' ? 'disabled' : 'active';
        (new User())->updateById((int) $player['id'], ['status' => $status]);
        $this->flash('success', "{$player['username']} is now $status.");

        return $this->back('/admin/players');
    }

    public function changeRole(string $id): Response
    {
        $player = $this->findPlayer($id);
        $this->guardSelf($player, 'You cannot change your own role.');

        $role = $this->request->string('role');
        if (!in_array($role, ['player', 'admin'], true)) {
            throw new HttpException(422, 'Invalid role.');
        }

        (new User())->updateById((int) $player['id'], ['role' => $role]);
        $this->flash('success', "{$player['username']} is now " . ($role === 'admin' ? 'an admin' : 'a player') . '.');

        return $this->back('/admin/players');
    }

    /* ---------------- Games ---------------- */

    public function games(): Response
    {
        $username = trim((string) $this->request->query('player', ''));
        $difficulty = array_key_exists((string) $this->request->query('difficulty'), difficulty_config()) ? $this->request->query('difficulty') : null;
        $result = in_array($this->request->query('result'), ['won', 'lost', 'abandoned'], true) ? $this->request->query('result') : null;
        $page = max(1, (int) $this->request->query('page', 1));

        [$games, $total] = (new Game())->adminSearch($username, $difficulty, $result, $page, self::PER_PAGE);

        return $this->view('admin/games', [
            'title'      => 'Games',
            'games'      => $games,
            'filters'    => ['player' => $username, 'difficulty' => $difficulty, 'result' => $result],
            'pagination' => paginate($total, $page, self::PER_PAGE),
        ]);
    }

    /** Remove a game (e.g. abuse) and rebuild that player's totals. */
    public function deleteGame(string $id): Response
    {
        $games = new Game();
        $game = $games->find((int) $id) ?? throw new HttpException(404, 'Game not found.');

        $games->deleteById((int) $game['id']);
        (new LeaderboardService())->recalculateUser((int) $game['user_id']);
        $this->flash('success', "Game #{$game['id']} deleted and the player's totals were recalculated.");

        return $this->back('/admin/games');
    }

    /* ---------------- Badges ---------------- */

    public function badges(): Response
    {
        $editId = (int) $this->request->query('edit', 0);

        return $this->view('admin/badges', [
            'title'        => 'Badges',
            'badges'       => (new Badge())->allWithUnlockCounts(),
            'editing'      => $editId ? (new Badge())->find($editId) : null,
            'requirements' => BadgeService::REQUIREMENTS,
            'icons'        => BadgeService::ICONS,
        ]);
    }

    public function saveBadge(): Response
    {
        $id = $this->request->int('id') ?: null;
        $input = $this->request->only(['name', 'description', 'icon', 'requirement_type', 'requirement_value', 'sort_order']);
        $redirect = '/admin/badges' . ($id ? "?edit=$id" : '');

        $validator = Validator::make($input, [
            'name'              => 'required|max:60',
            'description'       => 'required|max:255',
            'icon'              => 'required',
            'requirement_type'  => 'required',
            'requirement_value' => 'required|integer|min_value:1|max_value:10000000',
            'sort_order'        => 'nullable|integer',
        ]);

        $service = new BadgeService();
        $errors = $validator->errors() + $service->validate($input, $id);
        if ($errors) {
            throw $this->validationFailed($errors, $redirect);
        }

        $service->save($input, $id);
        $this->flash('success', $id ? 'Badge updated.' : 'Badge created.');

        return $this->redirect('/admin/badges');
    }

    public function deleteBadge(string $id): Response
    {
        $badges = new Badge();
        $badge = $badges->find((int) $id) ?? throw new HttpException(404, 'Badge not found.');
        $badges->deleteById((int) $badge['id']);
        $this->flash('success', "Badge \"{$badge['name']}\" deleted.");

        return $this->redirect('/admin/badges');
    }

    /* ---------------- Leaderboard ---------------- */

    public function leaderboard(): Response
    {
        $service = new LeaderboardService();

        return $this->view('admin/leaderboard', [
            'title' => 'Leaderboard management',
            'tabs'  => LeaderboardService::TABS,
            'board' => $service->board((string) $this->request->query('tab', 'global'), null, 100),
        ]);
    }

    public function recalculate(): Response
    {
        $count = (new LeaderboardService())->recalculateAll();
        $this->flash('success', "Recalculated totals, levels and streaks for $count players.");

        return $this->redirect('/admin/leaderboard');
    }

    private function findPlayer(string $id): array
    {
        return (new User())->find((int) $id) ?? throw new HttpException(404, 'Player not found.');
    }

    private function guardSelf(array $player, string $message): void
    {
        if ((int) $player['id'] === (int) $this->user()['id']) {
            $this->flash('error', $message);
            throw new \App\Core\ValidationException($this->back('/admin/players'));
        }
    }
}
