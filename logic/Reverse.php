<?php

class Reverse
{
    public const PARAMS = [
        'm' => 'message',
        // 'c' => 'clients', //TODO: think
    ];
    public const HELP = "Usage: php check.php test\nParams:\n";

    protected Dev $cfg;

    function __construct(array $params, bool $remote = false)
    {
        if (isset($params[1]))
            $params[1] = str_replace(strtolower(self::class) . '_', '', $params[1]);
        if ($remote) {
            $args = [self::class, $params['d']];
            foreach (self::PARAMS as $key => $item)
                $args[$item] = $params[$key] ?? $params[$item] ?? null;

            $params = $args;
        }

        $this->cfg = new Dev(...$params);
    }

    public function check(): void
    {
        echo "### " . strtoupper($this->cfg->name()) . "\n";
        if ($this->cfg->get('current') && Helper::changed($this->cfg)) {
            $this->cfg->set('current', false);
            $this->cfg->set(0, date_create()->format('c'));
        }

        (new Msg)->send($this->cfg);
    }

    public function request(): void
    {
        if (!$this->cfg->get('current')) {
            $this->cfg->set('current', true);
            $this->cfg->set(1, date_create()->format('c'));
            $this->cfg->set('ip', $_SERVER['REMOTE_ADDR'] ?? '');
        }

        (new Msg)->send($this->cfg);
        $this->cfg->unset('message');
    }
}
