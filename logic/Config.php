<?php
class Config
{
    private const DEFAULT_FILE = 'statuses';

    private string $file;
    private array $data = [];
    private array $origData = [];

    public function __construct(?string $file = null)
    {
        $this->file = ROOT . '/cfg/'
            . ($file ?? self::DEFAULT_FILE) . '.json';

        $this->data = $this->origData = is_file($this->file)
            ? json_decode(file_get_contents($this->file), true) : [];
    }

    public function __destruct()
    {
        foreach (explode(',', $this->data['reset'] ?? '') as $field)
            if (
                isset($this->origData[$field]) && $field !== 'reset' &&
                $this->origData[$field] !== ($this->data[$field] ?? null)
            ) $this->data[$field] = $this->origData[$field];
            else unset($this->data[$field]);
        if ($this->data !== $this->origData)
            file_put_contents($this->file, json_encode($this->data) . "\n");
    }

    public function getData()
    {
        return $this->data;
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
