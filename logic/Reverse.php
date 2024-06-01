<?php

class Reverse
{
    public const PARAMS = [
        'c' => 'clients', //TODO: think
    ];
    public const HELP = "Usage: php check.php test\nParams:\n";

    private Config $cfg;

    function __construct(array $params, bool $remote = false)
    {
        $this->cfg = new Config(self::class, $remote ? [
            1 => $params['d'] ?? $params['dev'] ?? $params['device'],
            'clients' => $params['c'] ?? $params['clients'] ?? 0,
        ] : $params);
    }

    public static function checkAll(array $argv): void
    {
        if (isset($argv[1])) {
            (new self($argv))->check();
        } else {
            foreach (Config::all(self::class) as $cfg) {
                (new self($cfg->getOrig()))->check();
            }
        }
    }

    public function check(): void
    {
        if ($this->cfg->get('current') && !Helper::changed($this->cfg)) {
            $this->cfg->set('current', false);
            $this->cfg->set(0, date_create()->format('c'));
            (new Message)->send($this->cfg);
        } else echo "Nothing changed.\n";
    }

    public function request(): void
    {
        if (!$this->cfg->get('current')) {
            $this->cfg->set('current', true);
            $this->cfg->set(1, date_create()->format('c'));
            $this->cfg->set('ip', $_SERVER['REMOTE_ADDR'] ?? '');
            (new Message)->send($this->cfg);
        } else echo "Nothing changed.\n";
    }
}
