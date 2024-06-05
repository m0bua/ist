<?php
class Config
{
    private const DIR = 'cfg';
    public const CFG_TYPES = [
        'direct',
        'reverse',
    ];
    private const PARAMS = [
        'tgc' => 'tgChat',
        'msg' => 'msgPattern',
        'c' => 'current',
        'w' => 'wait',
        'reset',
    ];

    private string $class;
    private string $name;
    private array $data = [];
    private array $origData = [];

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
        if (in_array($this->type(), self::CFG_TYPES)) $this->argv($argv);
    }

    function __destruct()
    {
        foreach (explode(',', $this->data['reset'] ?? '') as $field)
            if (
                isset($this->origData[$field]) && $field !== 'reset' &&
                $this->origData[$field] !== ($this->data[$field] ?? null)
            ) $this->data[$field] = $this->origData[$field];
            else unset($this->data[$field]);
        if ($this->data !== $this->origData)
            file_put_contents($this->file(), json_encode($this->data) . "\n");
    }

    public static function all(?string $class = null, bool $activeOnly = false): array
    {
        $configs = array_map(fn ($i) => explode('.', $i), scandir(Helper::path(self::DIR)));
        $configs = array_filter($configs, fn ($i) => count($i) === 2
            && $i[1] === 'json' && self::getDev($i[0], $class));
        $configs = array_map(fn ($i) => $i[0], $configs);

        foreach ($configs as $cfg) $all[self::getDev($cfg)] = new self($cfg);

        $all = array_filter($all ?? [], fn (self $c) =>
        Auth::client($c->name(), false));

        if ($activeOnly) $all = array_filter($all ?? [], fn (self $c) =>
        $c->get('active'));

        return $all ?? [];
    }

    public function change(string $field, mixed $val = null): bool
    {
        if (
            is_bool($this->get($field))
            || $field === 'active'
        ) {
            $this->set($field, !$this->get($field));
        } else {
            $this->set($field, $val);
        }

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

    public function name()
    {
        $name = $this->type();
        if (!empty($this->name)) $name .= '_' . $this->name;

        return $name;
    }

    private static function getDev(string $cfg, ?string $class = null): ?string
    {
        $result = $cfg;
        if (empty($class)) foreach (self::CFG_TYPES as $type)
            $result = str_replace($type . '_', '', $result);
        else
            $result = str_replace(strtolower($class) . '_', '', $result);

        return $cfg === $result ? null : $result;
    }

    private function file()
    {
        return Helper::path([self::DIR, $this->name() . '.json']);
    }

    private function type()
    {
        return strtolower($this->class);
    }

    private function argv(array $argv): self
    {
        $fields = array_merge(self::PARAMS, $this->class::PARAMS ?? []);
        $help = $this->class::HELP ?? "Params:\n";

        $cfg = [];

        $params = array_slice($argv, 2);
        $params = array_values($params) === $params
            ? implode('&', $params) : http_build_query($params);

        parse_str($params, $params);

        foreach ($fields ?? [] as $short => $param) {
            if (isset($params[$short])) $cfg[$param] = $params[$short];
            if (isset($params[$param])) $cfg[$param] = $params[$param];
            if (is_string($short)) $param .= '|' . $short;
            $help .= $param . "\n";
        }

        if (empty($this->class) || !isset($this->name)) die($help);

        foreach ($cfg as $key => $item) $this->set($key, $item);

        return $this;
    }
}
