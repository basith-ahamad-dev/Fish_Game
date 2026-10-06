<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\FishApiException;
use App\Services\GameException;
use App\Services\GameService;
use App\Services\StatisticsService;

class GameController extends Controller
{
    public function index(): Response
    {
        $user = $this->user();
        $difficulty = (string) $this->request->query('difficulty', 'easy');

        return $this->view('game/index', [
            'title'        => 'Play',
            'user'         => $user,
            'difficulties' => (new GameService())->difficulties(),
            'selected'     => array_key_exists($difficulty, difficulty_config()) ? $difficulty : 'easy',
            'winRate'      => (new StatisticsService())->winRate($user),
        ]);
    }

    /** POST /game/start {difficulty} */
    public function start(): Response
    {
        return $this->handle(fn (GameService $games) => $games->start(
            $this->user(),
            $this->request->string('difficulty')
        ));
    }

    /** POST /game/round {game_id} → next picture (image only, never the solution) */
    public function round(): Response
    {
        return $this->handle(fn (GameService $games) => $games->nextRound(
            $this->user(),
            $this->request->int('game_id')
        ));
    }

    /** POST /game/answer {game_id, answer|null} */
    public function answer(): Response
    {
        $raw = $this->request->input('answer');
        $guess = null;
        if ($raw !== null && $raw !== '') {
            $guess = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99]]);
            if ($guess === false) {
                return $this->json(['message' => 'Enter a whole number of fish.'], 422);
            }
        }

        return $this->handle(fn (GameService $games) => $games->answer(
            $this->user(),
            $this->request->int('game_id'),
            $guess
        ));
    }

    /** POST /game/abandon {game_id} */
    public function abandon(): Response
    {
        return $this->handle(fn (GameService $games) => $games->abandon(
            $this->user(),
            $this->request->int('game_id')
        ));
    }

    /** POST /game/result {game_id} → final summary computed by the server */
    public function result(): Response
    {
        return $this->handle(fn (GameService $games) => $games->result(
            $this->user(),
            $this->request->int('game_id')
        ));
    }

    /** GET /game/result/{id} — standalone result page */
    public function show(string $id): Response
    {
        $games = new GameService();
        try {
            $summary = $games->result($this->user(), (int) $id);
        } catch (GameException $e) {
            throw new HttpException($e->status === 404 ? 404 : 409, $e->getMessage());
        }

        return $this->view('game/result', [
            'title'   => 'Game result',
            'summary' => $summary,
            'rounds'  => $games->rounds((int) $id),
        ]);
    }

    private function handle(callable $action): Response
    {
        try {
            $result = $action(new GameService());
            AuthService::refresh();

            return $this->json($result);
        } catch (FishApiException $e) {
            return $this->json(['message' => $e->getMessage(), 'retry' => true], 503);
        } catch (GameException $e) {
            return $this->json(['message' => $e->getMessage()], $e->status);
        }
    }
}
