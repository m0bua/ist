<?php

namespace Helpers;

use ErrorException;
use PDO;

class DB
{
    private static $dsn;
    private static $username;
    private static $password;
    private static $options;

    private $pdo;

    public static function config(array $auth)
    {
        self::$dsn = "mysql:dbname={$auth['base']};host={$auth['host']}";
        self::$username = $auth['user'];
        self::$password = $auth['pass'];
        self::$options = $auth['opt'] ?? null;
    }

    public static function start()
    {
        return new self;
    }

    public function __construct()
    {
        $this->pdo = new PDO(
            self::$dsn,
            self::$username,
            self::$password,
            self::$options
        );
    }

    public function one(string $query)
    {
        return $this->query($query)->fetch();
    }

    public function all(string $query)
    {
        return $this->query($query)->fetchAll();
    }

    public function upsert(string $table, array $data)
    {
        $cols = array_column(self::start()->all("DESCRIBE $table"), 'Field');
        $data = array_filter($data, function ($k) use ($cols) {
            return in_array($k, $cols, true);
        }, ARRAY_FILTER_USE_KEY);
        if (empty($data)) return;

        $data = array_map(function ($i) {
            if (is_bool($i)) $i = (int)$i;
            return $i;
        }, $data);

        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $values = implode(', ', array_map(fn($i) => ":$i", $keys));
        $update = implode(', ', array_map(fn($i) => "$i=:$i", $keys));
        $query = "INSERT INTO $table ($fields) VALUES ($values)
            ON DUPLICATE KEY UPDATE $update";

        $result = $this->exec($query, $data);

        return $result;
    }

    private function query(string $query)
    {
        return $this->pdo->query($query, PDO::FETCH_ASSOC);
    }

    private function exec(string $query, array $data = [])
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $result = $stmt->execute($data);
        } catch (\PDOException $e) {
            var_dump($e->getMessage() . ", SQL: $query\n", $data);
        }

        return $result ?? false;
    }
}
