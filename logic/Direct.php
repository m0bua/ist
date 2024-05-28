<?php

class Direct
{
    public const PARAMS = [
        's' => 'server',
        't' => 'tries',
        'to' => 'timeout',
    ];
    public const HELP = "Usage: php direct.php test\nParams:\n";

    private Config $cfg;
    private bool $status = false;

    public function __construct($argv)
    {
        $this->cfg = new Config(self::class, $argv);
        if (empty($this->cfg->get('server')))
            die("No server selected!\n");
    }

    public function status(): void
    {
        $this->test();
        if (
            ($this->status || !Helper::changed($this->cfg))
            && ($this->cfg->get('current') !== $this->status)
        ) {
            $this->cfg->set('current', $this->status);
            $this->cfg->set((int)$this->status, date_create()->format('c'));
            (new Message)->send($this->cfg);
        } else echo "Nothing changed.\n";
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
