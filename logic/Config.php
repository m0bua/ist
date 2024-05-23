<?php
class Config
{
    private const DEFAULT_FILE = 'statuses';

    private string $file;
    private array $data = [];

    public function __construct(?string $file = null)
    {
        $this->file = ROOT . '/cfg/'
            . ($file ?? self::DEFAULT_FILE) . '.json';

        $this->data = is_file($this->file)
            ? json_decode(file_get_contents($this->file), true) : [];
    }

    public function __destruct()
    {
        file_put_contents($this->file, json_encode($this->data) . "\n");
    }

    public function get(string $field, mixed $default = null): mixed
    {
        return $this->data[$field] ?? $default;
    }

    public function set(string $field, mixed $value): void
    {
        $this->data[$field] = $value;
    }
}
