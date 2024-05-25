<?php
class Config
{
    private const DEFAULT_FILE = 'statuses';
    private const CFG_TYPES = [
        'direct',
    ];

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

    public static function all(): array
    {
        $configs = array_map(fn ($i) => explode('.', $i), scandir(ROOT . '/cfg/'));
        $configs = array_filter($configs, fn ($i) => count($i) === 2
            && $i[1] === 'json' && self::getDev($i[0]));
        $configs = array_map(fn ($i) => $i[0], $configs);

        foreach ($configs as $cfg)
            $result[self::getDev($cfg)] = new self($cfg);

        $keys = array_keys($result);
        $unic = array_count_values(array_map(fn ($i) => substr($i, 0, -1), $keys));
        foreach ($result as $k => $i) {
            $dev = substr($k, 0, -1);
            if ($unic[$dev] > 1) {
                $result[$dev][str_replace($dev, '', $k)] = $i;
                unset($result[$k]);
            }
        }

        return $result ?? [];
    }

    private static function getDev(string $cfg): ?string
    {
        $result = $cfg;
        foreach (self::CFG_TYPES as $type)
            $result = str_replace($type . '_', '', $result);

        return $cfg === $result ? null : $result;
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
