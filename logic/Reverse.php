<?php

class Reverse
{
    public const PARAMS = [
        'm' => 'message',
        // 'c' => 'clients', //TODO: think
    ];
    public const HELP = "Usage: php check.php test\nParams:\n";

    private Config $cfg;

    function __construct(array $params, bool $remote = false)
    {
        if ($remote) {
            $args = [self::class, $params['d']];
            foreach (self::PARAMS as $key => $item)
                $args[$item] = $params[$key] ?? $params[$item] ?? null;
        }

        $this->cfg = new Config(...($args ?? $params));
    }

    public static function checkAll(array $argv = []): void
    {
        if (empty($argv[1])) {
            foreach (array_keys(Config::all(self::class, true)) as $dev) {
                (new self([self::class, $dev]))->check();
            }
        } else (new self($argv))->check();
    }

    public function check(): void
    {
        echo "### " . strtoupper($this->cfg->name()) . "\n";
        if ($this->cfg->get('current') && Helper::changed($this->cfg)) {
            $this->cfg->set('current', false);
            $this->cfg->set(0, date_create()->format('c'));
        }

        (new Message)->send($this->cfg);
    }

    public function request(): void
    {
        if (!$this->cfg->get('current')) {
            $this->cfg->set('current', true);
            $this->cfg->set(1, date_create()->format('c'));
            $this->cfg->set('ip', $_SERVER['REMOTE_ADDR'] ?? '');
        }

        (new Message)->send($this->cfg);
        $this->cfg->unset('message');
    }
}