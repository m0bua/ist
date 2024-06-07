<?php

class Dev extends Cfg
{
    public const CFG_TYPES = [
        'direct',
        'reverse',
    ];
    protected const PARAMS = [
        'tgc' => 'tgChat',
        'msg' => 'msgPattern',
        'c' => 'current',
        'w' => 'wait',
        'reset',
    ];

    function __construct(...$argv)
    {
        parent::__construct(...$argv);
        if (in_array($this->type(), self::CFG_TYPES)) $this->argv($argv);
    }

    public static function all(?string $class = null, bool $activeOnly = false): array
    {
        foreach (self::list($class) as $item)
            $cfgs[$item] = new self($item);
        if (empty($cfgs)) return [];

        $cfgs = array_filter($cfgs, fn (self $c) => Auth::client($c->name(), false));
        if ($activeOnly) $cfgs = array_filter($cfgs, fn (self $c) => $c->get('active'));

        return $cfgs;
    }

    public static function list(?string $class = null)
    {
        $list = array_map(fn ($i) => explode('.', $i), scandir(Helper::path(self::DIR)));
        $list = array_filter($list, fn ($i) => count($i) === 2
            && $i[1] === 'json' && self::getDev($i[0], $class));
        $list = array_map(fn ($i) => $i[0], $list);

        return $list;
    }

    public static function runAll($paralel = false, $mode = 'r')
    {
        foreach (self::CFG_TYPES as $type) {
            $cmd = Helper::path("$type.php");
            $class = ucfirst($type);
            foreach (array_keys(self::all($type, true)) as $dev) {
                if ($paralel) $stdouts[] = popen("php $cmd $dev", $mode);
                else (new $class([$dev]))->check();
            }
        }

        foreach ($stdouts ?? [] as $stdout)
            while (!feof($stdout)) echo fgets($stdout);
    }

    public function key(string $key)
    {
        return $this->get('name', self::getDev($key));
    }

    protected static function getDev(string $cfg, ?string $class = null): ?string
    {
        $result = $cfg;
        if (empty($class)) foreach (self::CFG_TYPES as $type)
            $result = str_replace($type . '_', '', $result);
        else
            $result = str_replace(strtolower($class) . '_', '', $result);

        return $cfg === $result ? null : $result;
    }

    protected function argv(array $argv): self
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
