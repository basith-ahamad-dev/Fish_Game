<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Game;
use App\Models\GameRound;
use App\Models\User;
use PDOException;

/**
 * Game sessions on top of the Fish API.
 *
 * Flow: start() → nextRound() → answer() → … until lives run out or every round is played.
 * The solution for each picture never leaves the server before it is answered, timing is
 * measured with server clocks, and the score is computed here — the browser only sends a guess.
 */
class GameService
{
    private Game $games;
    private GameRound $rounds;
    private User $users;

    public function __construct(
        private FishGameService $fish = new FishGameService(),
        private XPService $xp = new XPService(),
        private BadgeService $badges = new BadgeService(),
        private LeaderboardService $leaderboard = new LeaderboardService(),
    ) {
        $this->games = new Game();
        $this->rounds = new GameRound();
        $this->users = new User();
    }

    public function difficulties(): array
    {
        return difficulty_config();
    }

    public function start(array $user, string $difficulty): array
    {
        $config = difficulty_config($difficulty);
        if (!$config) {
            throw new GameException('Choose Easy, Medium or Hard.', 422);
        }

        // Only one live game per player: leaving one mid-way counts as abandoned.
        $abandoned = false;
        foreach ($this->games->inProgressForUser((int) $user['id']) as $open) {
            Database::transaction(function () use ($open, $user) {
                $game = $this->games->lockForUser((int) $open['id'], (int) $user['id']);
                if ($game && $game['status'] === 'in_progress') {
                    $this->finish($game, 'abandoned');
                }
            });
            $abandoned = true;
        }

        $id = $this->games->create((int) $user['id'], $difficulty, $config['rounds'], $config['lives']);

        return [
            'game_id'            => $id,
            'difficulty'         => $difficulty,
            'label'              => $config['label'],
            'rounds'             => $config['rounds'],
            'time_limit'         => $config['time_limit'],
            'lives'              => $config['lives'],
            'score_mult'         => $config['score_mult'],
            'xp_mult'            => $config['xp_mult'],
            'abandoned_previous' => $abandoned,
        ];
    }

    /**
     * Serve the next picture. Requesting a new one while a picture is still open forfeits it,
     * so refreshing cannot be used to skip a hard picture.
     */
    public function nextRound(array $user, int $gameId): array
    {
        $state = Database::transaction(function () use ($user, $gameId) {
            $game = $this->lockLiveGame($user, $gameId);
            $pending = $this->rounds->pendingForGame($gameId);
            if (!$pending) {
                return ['finished' => false, 'forfeited' => null];
            }

            $outcome = $this->resolve($game, $pending, null);

            return ['finished' => $outcome['finished'], 'forfeited' => $outcome];
        });

        if ($state['finished']) {
            return ['finished' => true, 'forfeited' => $state['forfeited']];
        }

        // Network call happens outside the transaction so no row lock is held while waiting.
        $puzzle = $this->fish->fetchPuzzle();

        return Database::transaction(function () use ($user, $gameId, $puzzle, $state) {
            $game = $this->lockLiveGame($user, $gameId);
            if ($this->rounds->pendingForGame($gameId)) {
                throw new GameException('A picture is already in play for this game.');
            }

            $number = $this->rounds->countForGame($gameId) + 1;
            if ($number > (int) $game['total_rounds']) {
                throw new GameException('All rounds have been played.');
            }

            try {
                $this->rounds->create([
                    'game_id'      => $gameId,
                    'round_number' => $number,
                    'fish_count'   => $puzzle['fish'],
                    'chest_count'  => $puzzle['chest'],
                    'served_ms'    => $this->nowMs(),
                ]);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    throw new GameException('A picture is already in play for this game.');
                }
                throw $e;
            }

            $config = difficulty_config($game['difficulty']);

            return [
                'finished'     => false,
                'forfeited'    => $state['forfeited'],
                'round'        => $number,
                'total_rounds' => (int) $game['total_rounds'],
                'time_limit'   => $config['time_limit'],
                'lives'        => (int) $game['lives_left'],
                'score'        => (int) $game['score'],
                'image'        => $puzzle['image'],
            ];
        });
    }

    /** Resolve the open picture with the player's guess (null = ran out of time). */
    public function answer(array $user, int $gameId, ?int $guess): array
    {
        return Database::transaction(function () use ($user, $gameId, $guess) {
            $game = $this->lockLiveGame($user, $gameId);
            $round = $this->rounds->pendingForGame($gameId)
                ?? throw new GameException('There is no picture waiting for an answer.');

            return $this->resolve($game, $round, $guess);
        });
    }

    /** Player quit mid-game: close it as abandoned (keeps XP for correct answers, resets the streak). */
    public function abandon(array $user, int $gameId): array
    {
        return Database::transaction(function () use ($user, $gameId) {
            return $this->finish($this->lockLiveGame($user, $gameId), 'abandoned');
        });
    }

    /** Final summary of a completed game. */
    public function result(array $user, int $gameId): array
    {
        $game = $this->games->findForUser($gameId, (int) $user['id'])
            ?? throw new GameException('Game not found.', 404);

        if ($game['status'] !== 'completed') {
            throw new GameException('This game is still in progress.');
        }

        return $this->summary($game);
    }

    public function rounds(int $gameId): array
    {
        return $this->rounds->forGame($gameId);
    }

    private function lockLiveGame(array $user, int $gameId): array
    {
        $game = $this->games->lockForUser($gameId, (int) $user['id'])
            ?? throw new GameException('Game not found.', 404);

        if ($game['status'] !== 'in_progress') {
            throw new GameException('This game has already finished.');
        }

        return $game;
    }

    private function resolve(array $game, array $round, ?int $guess): array
    {
        $config = difficulty_config($game['difficulty']);
        $now = $this->nowMs();
        $elapsed = $now - (int) $round['served_ms'];
        $limitMs = $config['time_limit'] * 1000;

        $timedOut = $guess === null || $elapsed > $limitMs + config('game.scoring.grace_ms');
        $correct = !$timedOut && $guess === (int) $round['fish_count'];
        $points = $correct ? $this->roundPoints($config, $elapsed) : 0;

        $this->rounds->updateById((int) $round['id'], [
            'answer'      => $timedOut ? null : $guess,
            'status'      => $timedOut ? 'timeout' : 'answered',
            'is_correct'  => $correct ? 1 : 0,
            'points'      => $points,
            'answered_ms' => $now,
        ]);

        $game['score'] = (int) $game['score'] + $points;
        $game['rounds_played'] = (int) $game['rounds_played'] + 1;
        $game['correct_answers'] = (int) $game['correct_answers'] + ($correct ? 1 : 0);
        $game['fish_caught'] = (int) $game['fish_caught'] + ($correct ? (int) $round['fish_count'] : 0);
        $game['lives_left'] = max(0, (int) $game['lives_left'] - ($correct ? 0 : 1));

        $this->games->updateById((int) $game['id'], [
            'score'           => $game['score'],
            'rounds_played'   => $game['rounds_played'],
            'correct_answers' => $game['correct_answers'],
            'fish_caught'     => $game['fish_caught'],
            'lives_left'      => $game['lives_left'],
        ]);

        $finished = $game['lives_left'] === 0 || $game['rounds_played'] >= (int) $game['total_rounds'];

        return [
            'round'        => (int) $round['round_number'],
            'correct'      => $correct,
            'timed_out'    => $timedOut,
            'solution'     => (int) $round['fish_count'],
            'chests'       => (int) $round['chest_count'],
            'points'       => $points,
            'score'        => $game['score'],
            'lives'        => $game['lives_left'],
            'correct_total' => $game['correct_answers'],
            'rounds_played' => $game['rounds_played'],
            'total_rounds' => (int) $game['total_rounds'],
            'finished'     => $finished,
            'summary'      => $finished ? $this->finish($game, $game['lives_left'] > 0 ? 'won' : 'lost') : null,
        ];
    }

    /**
     * Close the game and apply its result to the player: score, XP/level, streaks, rank, badges.
     * Must run inside a transaction with the game row locked.
     */
    private function finish(array $game, string $result): array
    {
        $config = difficulty_config($game['difficulty']);
        $scoring = config('game.scoring');
        $userId = (int) $game['user_id'];

        $this->rounds->closePending((int) $game['id'], $this->nowMs());

        $correct = (int) $game['correct_answers'];
        $perfect = $result === 'won' && $correct === (int) $game['total_rounds'];
        $perfectBonus = $perfect ? (int) round($scoring['perfect_bonus'] * $config['score_mult']) : 0;
        $score = (int) $game['score'] + $perfectBonus;
        $xpEarned = $this->xp->calculate($game['difficulty'], $correct, $result, $perfect);

        $user = $this->users->lockById($userId);
        $progress = $this->xp->award((int) $user['xp'], $xpEarned);
        $won = $result === 'won';
        $streak = $won ? (int) $user['current_streak'] + 1 : 0;
        $previousBest = (int) $user['best_score'];

        $this->games->updateById((int) $game['id'], [
            'status'    => 'completed',
            'result'    => $result,
            'score'     => $score,
            'xp_earned' => $xpEarned,
            'played_at' => date('Y-m-d H:i:s'),
        ]);

        $this->users->updateById($userId, [
            'xp'             => $progress['xp'],
            'level'          => $progress['level_after'],
            'total_score'    => (int) $user['total_score'] + $score,
            'games_played'   => (int) $user['games_played'] + 1,
            'games_won'      => (int) $user['games_won'] + ($won ? 1 : 0),
            'best_score'     => max($previousBest, $score),
            'fish_caught'    => (int) $user['fish_caught'] + (int) $game['fish_caught'],
            'current_streak' => $streak,
            'best_streak'    => max((int) $user['best_streak'], $streak),
        ]);

        $user = $this->users->find($userId);
        $rank = $this->leaderboard->globalRank($user);
        $unlocked = $this->badges->evaluate($user, $rank);

        $data = [
            'perfect'       => $perfect,
            'perfect_bonus' => $perfectBonus,
            'personal_best' => $score > $previousBest && $score > 0,
            'previous_best' => $previousBest,
            'level_before'  => $progress['level_before'],
            'level_after'   => $progress['level_after'],
            'leveled_up'    => $progress['leveled_up'],
            'xp_total'      => $progress['xp'],
            'streak'        => $streak,
            'badges'        => $unlocked,
        ];

        $this->games->updateById((int) $game['id'], [
            'rank_after' => $rank,
            'game_data'  => json_encode($data),
        ]);

        return $this->summary($this->games->find((int) $game['id']));
    }

    private function summary(array $game): array
    {
        $data = json_decode((string) $game['game_data'], true) ?: [];
        $config = difficulty_config($game['difficulty']);

        return [
            'game_id'          => (int) $game['id'],
            'difficulty'       => $game['difficulty'],
            'difficulty_label' => $config['label'] ?? ucfirst($game['difficulty']),
            'result'           => $game['result'],
            'score'            => (int) $game['score'],
            'xp_earned'        => (int) $game['xp_earned'],
            'correct'          => (int) $game['correct_answers'],
            'rounds_played'    => (int) $game['rounds_played'],
            'total_rounds'     => (int) $game['total_rounds'],
            'fish_caught'      => (int) $game['fish_caught'],
            'rank'             => $game['rank_after'] !== null ? (int) $game['rank_after'] : null,
            'played_at'        => $game['played_at'],
            'perfect'          => (bool) ($data['perfect'] ?? false),
            'perfect_bonus'    => (int) ($data['perfect_bonus'] ?? 0),
            'personal_best'    => (bool) ($data['personal_best'] ?? false),
            'level_before'     => (int) ($data['level_before'] ?? 1),
            'level_after'      => (int) ($data['level_after'] ?? 1),
            'leveled_up'       => (bool) ($data['leveled_up'] ?? false),
            'progress'         => (new LevelService())->getXPProgress((int) ($data['xp_total'] ?? 0)),
            'streak'           => (int) ($data['streak'] ?? 0),
            'badges'           => $data['badges'] ?? [],
        ];
    }

    private function roundPoints(array $config, int $elapsedMs): int
    {
        $scoring = config('game.scoring');
        $limitMs = $config['time_limit'] * 1000;
        $timeLeft = max(0, $limitMs - $elapsedMs) / $limitMs;
        $points = $scoring['base_points'] + $scoring['max_speed_bonus'] * $timeLeft;

        return (int) round($points * $config['score_mult']);
    }

    private function nowMs(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
