<?php

class Direct
{
    private const PARAMS = [
        's' => 'server',
        'tgc' => 'tgChat',
        'msg' => 'msgPattern',
        'w' => 'wait',
        't' => 'tries',
        'to' => 'timeout',
        'c' => 'current',
        'reset',
    ];

    private string $dev;
    private Config $cfg;
    private bool $status = false;

    public function __construct($argv)
    {
        $cfg = [];
        $example = "Usage: php direct.php test\nParams:\n";
        parse_str(implode('&', array_slice($argv, 2)), $params);
        foreach (self::PARAMS as $short => $param) {
            if (isset($params[$short])) $cfg[$param] = $params[$short];
            if (isset($params[$param])) $cfg[$param] = $params[$param];
            if (is_string($short)) $param .= '|' . $short;
            $example .= $param . "\n";
        }

        if (count($argv) < 2) {
            die($example);
        }
        $this->dev = $argv[1];

        $this->cfg = new Config('direct_' . $this->dev);
        foreach ($cfg as $key => $item) $this->cfg->set($key, $item);

        if (empty($this->cfg->get('server')))
            die("No server selected!\n");
    }

    public function status(): int
    {
        $this->test();
        $wait = date_create($this->cfg->get('wait', '-180 sec'));
        $oDate = $this->cfg->get((int)!$this->status, '');
        if (
            ($this->status || empty($oDate) || date_create($oDate) < $wait)
            && ($this->cfg->get('current') !== $this->status)
        ) {
            $this->cfg->set('current', $this->status);
            $this->cfg->set((int)$this->status, date_create()->format('c'));
            $msg = $this->cfg->get('msgPattern')
                ? Helper::msgPrepare($this->cfg->get('msgPattern'), [
                    'dev' => $this->dev,
                    'status' => $this->status,
                    'after' => Helper::after($oDate),
                ]) : ($this->status ? '🟢' : '🔴')
                . ' ' . $this->dev . ' status is '
                . ($this->status ? 'on' : 'off')
                . Helper::after($oDate, true) . '.';
        }

        if (isset($msg)) (new Message)->send($msg, $this->cfg->get('tgChat'));
        else echo "Nothing changed.\n";

        return (int)!$this->status;
    }

    protected function test(): void
    {
        $tries = $this->cfg->get('tries') ?? 5;
        $wait = $this->cfg->get('timeout') ?? 5;
        $server = $this->cfg->get('server');
        $port = 443;
        if (strpos($server, ':')) [$server, $port] = explode(':', $server);

        for ($i = 1; $i <= $tries; $i++) {
            if (!$this->status) {
                set_error_handler(function () use ($server, $port, $wait) {
                    echo "No connection to $server:$port, waiting $wait sec.\n";
                });
                $result = !!fSockOpen($server, $port, $_, $_, $wait);
                restore_error_handler();
            } else $result = true;

            if ($this->status !== $result) {
                echo "Connected to $server:$port!\n";
                $this->status = $result;
            }
        }
    }
}
