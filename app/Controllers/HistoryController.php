<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Game;

class HistoryController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): Response
    {
        $difficulty = $this->request->query('difficulty');
        $difficulty = array_key_exists((string) $difficulty, difficulty_config()) ? $difficulty : null;
        $result = in_array($this->request->query('result'), ['won', 'lost', 'abandoned'], true) ? $this->request->query('result') : null;
        $page = max(1, (int) $this->request->query('page', 1));

        [$games, $total] = (new Game())->paginateForUser((int) $this->user()['id'], $difficulty, $result, $page, self::PER_PAGE);

        return $this->view('history/index', [
            'title'      => 'Game history',
            'games'      => $games,
            'filters'    => ['difficulty' => $difficulty, 'result' => $result],
            'pagination' => paginate($total, $page, self::PER_PAGE),
        ]);
    }
}
