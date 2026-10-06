<?php

namespace App\Models;

use App\Core\Model;

class LoginAttempt extends Model
{
    protected string $table = 'login_attempts';

    public function record(string $ip, string $identifier): void
    {
        $this->execute(
            'INSERT INTO login_attempts (ip_address, identifier) VALUES (?, ?)',
            [$ip, mb_strtolower($identifier)]
        );
    }

    public function countRecent(string $ip, string $identifier, int $minutes): int
    {
        return (int) $this->fetchColumn(
            'SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND identifier = ? AND attempted_at >= ?',
            [$ip, mb_strtolower($identifier), $this->since($minutes)]
        );
    }

    public function countRecentByIp(string $ip, int $minutes): int
    {
        return (int) $this->fetchColumn(
            'SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at >= ?',
            [$ip, $this->since($minutes)]
        );
    }

    /** Seconds until the oldest counted attempt expires. */
    public function secondsUntilRelease(string $ip, string $identifier, int $minutes): int
    {
        $oldest = $this->fetchColumn(
            'SELECT MIN(attempted_at) FROM login_attempts WHERE ip_address = ? AND identifier = ? AND attempted_at >= ?',
            [$ip, mb_strtolower($identifier), $this->since($minutes)]
        );

        return $oldest ? max(0, strtotime($oldest) + $minutes * 60 - time()) : 0;
    }

    public function clear(string $ip, string $identifier): void
    {
        $this->execute(
            'DELETE FROM login_attempts WHERE ip_address = ? AND identifier = ?',
            [$ip, mb_strtolower($identifier)]
        );
    }

    public function pruneOlderThan(int $minutes): void
    {
        $this->execute('DELETE FROM login_attempts WHERE attempted_at < ?', [$this->since($minutes)]);
    }

    private function since(int $minutes): string
    {
        return date('Y-m-d H:i:s', time() - $minutes * 60);
    }
}
