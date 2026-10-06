<?php

namespace App\Models;

use App\Core\Model;

class RememberToken extends Model
{
    protected string $table = 'remember_tokens';

    protected array $fillable = ['user_id', 'selector', 'token_hash', 'expires_at'];

    public function create(int $userId, string $selector, string $tokenHash, string $expiresAt): void
    {
        $this->insert([
            'user_id'    => $userId,
            'selector'   => $selector,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findValid(string $selector): ?array
    {
        return $this->fetch(
            'SELECT * FROM remember_tokens WHERE selector = ? AND expires_at > NOW()',
            [$selector]
        );
    }

    public function deleteBySelector(string $selector): void
    {
        $this->execute('DELETE FROM remember_tokens WHERE selector = ?', [$selector]);
    }

    public function deleteForUser(int $userId): void
    {
        $this->execute('DELETE FROM remember_tokens WHERE user_id = ?', [$userId]);
    }

    public function pruneExpired(): void
    {
        $this->execute('DELETE FROM remember_tokens WHERE expires_at <= NOW()');
    }
}
