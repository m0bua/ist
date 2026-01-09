<?php

namespace Points;

use Dev;
use Helpers\{Helper, Msg};
use Parts\Point;

class Request implements Point
{
    protected Dev $cfg;
    protected bool $status = false;

    public function check(array $data = []): void
    {
        echo "### " . $this->cfg->name() . "\n";
        $this->cfg->set('updated', Helper::date());
        $this->test();
        if (
            ($this->status || Helper::changed($this->cfg))
            && ($this->cfg->get('status') != $this->status)
        ) {
            $this->cfg->set('status', (int)$this->status);
            $this->cfg->set((int)$this->status, Helper::date());
        }

        (new Msg)->send($this->cfg);
    }

    public static function init(Dev $cfg): self
    {
        $self = new self;
        $self->cfg = $cfg;

        return $self;
    }

    protected function test(): void
    {
        $server = $this->cfg->get('address');
        if (empty($server)) exit("No server selected!\n");
        if (strpos($server, ':')) [$server, $port] = explode(':', $server);

        if (empty($port)) $this->ping($server);
        else $this->fSockOpen($server, $port);
    }

    protected function ping(string $server): void
    {
        $tries = (int)$this->cfg->get('params.tries', 5);
        $wait = (int)$this->cfg->get('params.timeout', 5);
        $cmd = "ping $server -c$tries -W$wait -A";
        $output = shell_exec($cmd);
        $exp = explode("\n", $output);
        $result = $exp[count($exp) - 3] ?? $output;
        [$recived] = explode(' ', explode(', ', $result)[1] ?? $result);

        $this->status = $recived > 0;

        echo $output;
    }

    protected function fSockOpen(string $server, int $port): void
    {
        $tries = (int)$this->cfg->get('params.tries', 5);
        $wait = (int)$this->cfg->get('params.timeout', 5);

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
