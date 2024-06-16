<?php
class Cfg
{
    protected const DIR = 'cfg';

    protected string $class;
    protected string $name;
    protected array $data = [];
    protected array $origData = [];

    function __construct(...$argv)
    {
        $first = $argv[0] ?? '';
        $file = pathinfo($first, PATHINFO_FILENAME);
        if (pathinfo($first, PATHINFO_BASENAME) === $file) {
            [$file] = explode('_', $file);
            $this->name = trim(str_replace($file, '', $first), '_');
        }
        $this->class = ucfirst($file);
        if (!empty($argv[1])) $this->name = $argv[1];

        $this->data = $this->origData = is_file($this->file())
            ? json_decode(file_get_contents($this->file()), true) ?? [] : [];
    }

    function __destruct()
    {
        foreach (explode(',', $this->data['reset'] ?? '') as $field)
            if (
                isset($this->origData[$field]) && $field !== 'reset' &&
                $this->origData[$field] !== ($this->data[$field] ?? null)
            ) $this->data[$field] = $this->origData[$field];
            else unset($this->data[$field]);
        if ($this->data !== $this->origData) {
            self::sort($this->data);
            file_put_contents($this->file(), json_encode($this->data) . "\n");
        }
    }

    public function change(string $field, mixed $val = null): bool
    {
        if (is_bool($this->get($field)) || $field === 'active')
            $this->set($field, !$this->get($field));
        else $this->set($field, $val);

        return true;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getOrig(?string $field = null, mixed $default = null): mixed
    {
        return empty($field) ? $this->origData
            : $this->origData[$field] ?? $default;
    }

    public function get(?string $field = null, mixed $default = null): mixed
    {
        return Helper::getArrayKey($this->data, $field, $default);
    }

    public function set(string $field, mixed $value): void
    {
        $this->data[$field] = $value;
    }

    public function unset(string $field): void
    {
        unset($this->data[$field]);
    }

    public function changed(string $field)
    {
        $orig = $this->origData[$field] ?? null;
        $new = $this->data[$field] ?? null;

        return !($orig === $new);
    }

    public function dev()
    {
        return $this->get('name', $this->name ?? '');
    }
    public function name()
    {
        $name = $this->type();
        if (!empty($this->name)) $name .= '_' . $this->name;

        return $name;
    }

    protected function file()
    {
        return Helper::path([self::DIR, $this->name() . '.json']);
    }

    protected function type()
    {
        return strtolower($this->class);
    }

    protected static function sort(array &$data): void
    {
        ksort($data, SORT_STRING);
        foreach ($data as $item)
            if (is_array($item)) self::sort($item);
    }
}
