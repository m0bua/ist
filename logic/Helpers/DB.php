<?php

namespace Helpers;

use PDO;
use DateTime;
use PDOException;
use PDOStatement;

class DB
{
    private static $dsn;
    private static $username;
    private static $password;
    private static $options;

    private static array $tableMeta = [];

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
        $this->pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );
    }

    public function one(string $query, array $params = []): array
    {
        try {
            return $this->query($query, $params)->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function all(string $query, array $params = []): array
    {
        try {
            return $this->query($query, $params)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function upsert(string $table, array $data)
    {
        if (!isset(self::$tableMeta[$table])) {
            $schema = $this->all("DESCRIBE `$table` ");
            self::$tableMeta[$table] = [
                'cols' => array_column($schema, 'Field'),
                'keys' => array_column(array_filter($schema, fn($f) => !empty($f['Key'])), 'Field')
            ];
        }
        $data = array_intersect_key($data, array_flip(self::$tableMeta[$table]['cols']));
        if (empty($data)) return false;

        $data = array_map(fn($i) => match (true) {
            $i instanceof DateTime => $i->format('Y-m-d H:i:s'),
            is_bool($i) => (int)$i,
            default => $i
        }, $data);

        $keys = array_keys($data);
        $cols = array_diff($keys, self::$tableMeta[$table]['keys']);
        $fields = implode(', ', array_map(fn($i) => "`$i`", $keys));
        $values = implode(', ', array_map(fn($i) => ":$i", $keys));
        $query = "INTO `$table` ($fields) VALUES ($values)";

        $query = empty($cols) ? "INSERT IGNORE $query"
            : "INSERT $query ON DUPLICATE KEY UPDATE "
            . implode(', ', array_map(fn($k) => "`$k` = VALUES(`$k`)", $cols));

        return $this->exec($query, $data);
    }

    private function query(string $query, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return $stmt;
    }

    private function exec(string $query, array $data = []): bool
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $result = $stmt->execute($data);
        } catch (\PDOException $e) {
            echo $e->getMessage() . ", SQL: $query\n";
        }

        return $result ?? false;
    }
}
