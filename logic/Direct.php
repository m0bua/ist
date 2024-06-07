<?php

class Direct
{
    public const PARAMS = [
        's' => 'server',
        't' => 'tries',
        'to' => 'timeout',
    ];
    public const HELP = "Usage: php direct.php test\nParams:\n";

    protected Dev $cfg;
    protected bool $status = false;

    function __construct($argv)
    {
        if (isset($argv[1]))
            $argv[1] = str_replace(strtolower(self::class) . '_', '', $argv[1]);
        $this->cfg = new Dev(...$argv);
        if (empty($this->cfg->get('server')))
            die("No server selected!\n");
    }

    public function check(): void
    {
        echo "### " . strtoupper($this->cfg->name()) . "\n";
        $this->test();
        if (
            ($this->status || Helper::changed($this->cfg))
            && ($this->cfg->get('current') !== $this->status)
        ) {
            $this->cfg->set('current', $this->status);
            $this->cfg->set((int)$this->status, date_create()->format('c'));
        }

        (new Msg)->send($this->cfg);
    }

    protected function test(): void
    {
        $server = $this->cfg->get('server');
        if (strpos($server, ':')) [$server, $port] = explode(':', $server);

        if (empty($port)) $this->ping($server);
        else $this->fSockOpen($server, $port);
    }

    protected function ping(string $server)
    {
        $tries = (int)$this->cfg->get('tries', 5);
        $wait = (int)$this->cfg->get('timeout', 5);
        $cmd = "ping $server -c$tries -W$wait -A";
        $output = shell_exec($cmd);
        $exp = explode("\n", $output);
        $result = $exp[count($exp) - 3];
        [$recived] = explode(' ', explode(', ', $result)[1]);

        $this->status = $recived > 0;
        echo "$output";
    }

    protected function fSockOpen(string $server, int $port)
    {
        $tries = (int)$this->cfg->get('tries', 5);
        $wait = (int)$this->cfg->get('timeout', 5);

        for ($i = 0; $i < $tries; $i++) {
            if ($this->status) break;

            set_error_handler(function () use ($server, $port, $wait) {
                echo "No connection to $server:$port, waiting $wait sec.\n";
            });
            if (!!fSockOpen($server, $port, $_, $_, $wait)) {
                echo "Connected to $server:$port!\n";
                $this->status = true;
            }
            restore_error_handler();
        }
    }
}
