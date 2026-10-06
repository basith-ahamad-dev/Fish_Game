<?php

namespace App\Core;

use PDO;

abstract class Model
{
    protected string $table;

    /** Columns that insert()/updateById() are allowed to write. */
    protected array $fillable = [];

    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function find(int $id): ?array
    {
        return $this->fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    public function insert(array $data): int
    {
        $data = $this->onlyFillable($data);
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $this->execute("INSERT INTO {$this->table} ($columns) VALUES ($placeholders)", array_values($data));

        return (int) $this->db->lastInsertId();
    }

    public function updateById(int $id, array $data): int
    {
        $data = $this->onlyFillable($data);
        if (!$data) {
            return 0;
        }

        $set = implode(', ', array_map(fn ($col) => "$col = ?", array_keys($data)));

        return $this->execute("UPDATE {$this->table} SET $set WHERE id = ?", [...array_values($data), $id]);
    }

    public function deleteById(int $id): int
    {
        return $this->execute("DELETE FROM {$this->table} WHERE id = ?", [$id]);
    }

    public function count(): int
    {
        return (int) $this->fetchColumn("SELECT COUNT(*) FROM {$this->table}");
    }

    protected function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    protected function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn();
    }

    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    private function onlyFillable(array $data): array
    {
        return array_intersect_key($data, array_flip($this->fillable));
    }
}
