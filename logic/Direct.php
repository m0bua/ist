<?php

class Direct
{
    private string $dev;
    private Config $cfg;
    private bool $status = false;

    public function __construct($argv)
    {
        if (count($argv) < 2 || count($argv) > 5)
            die('Usage: php direct.php test google.com {telegram_chat_id}'
                . "'{status:\ud83d\udd34|\ud83d\udfe2} "
                . 'Test {status:offline|online}'
                . "{after: after #}!'\n");
        $this->dev = $argv[1];
        $this->cfg = new Config('direct_' . $this->dev);
        if (isset($argv[2]))
            $this->cfg->set('server', $argv[2]);
        if (empty($this->cfg->get('server')))
            die("No server selected!\n");
        if (isset($argv[3]) && is_numeric($argv[3]))
            $this->cfg->set('tgChat', $argv[3]);
        if (isset($argv[4]))
            $this->cfg->set('msgPattern', $argv[4]);
    }

    public function status(): int
    {
        $this->test();
        $wait = date_create($this->cfg->get('wait', '-1 sec'));
        $oDate = $this->cfg->get((int)!$this->status, '');
        if (
            (empty($oDate) || date_create($oDate) < $wait)
            && ($this->cfg->get('current') !== $this->status)
        ) {
            $this->cfg->set('current', $this->status);
            $this->cfg->set((int)$this->status, date_create()->format('c'));
            $msg = $this->cfg->get('msgPattern')
                ? Helper::msgPrepare($this->cfg->get('msgPattern'), [
                    'dev' => $this->dev,
                    'status' => $this->status,
                    'after' => Helper::after($oDate, false),
                ]) : ($this->status ? '🟢' : '🔴')
                . ' ' . $this->dev . ' status is '
                . ($this->status ? 'on' : 'off')
                . Helper::after($oDate) . '.';
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
