<?php

namespace Points;

use tuyapiphp\TuyaApi;

use Dev;
use Helpers\{Helper, Msg};
use Parts\Point;

class Tuya implements Point
{
    const STATUSES = [
        0 => 'off',
        1 => 'on',
        2 => 'low',
        3 => 'high',
    ];

    protected Dev $cfg;
    protected int $status;

    public function check(array $data = []): void
    {
        echo "### " . $this->cfg->name() . "\n";
        $this->cfg->statuses = array_keys(self::STATUSES);
        $this->cfg->fields = ['voltage'];
        $this->cfg->set('updated', Helper::date());
        $this->test();
        if (
            isset($this->status) && Helper::changed($this->cfg, 'status')
            && ($this->cfg->get('status') !== $this->status)
        ) {
            $this->cfg->set('status', $this->status);
            $this->cfg->set($this->status, Helper::date());
        }

        (new Msg)->send($this->cfg);
    }

    public static function init(Dev $cfg): self
    {
        $self = new self;
        $self->cfg = $cfg;

        return $self;
    }

    private function test()
    {
        $tuya = new TuyaApi([
            'accessKey' => $this->cfg->get('params.cliId'),
            'secretKey' => $this->cfg->get('params.cliSecret'),
            'baseUrl' => $this->cfg->get('address')
        ]);
        $token = $tuya->token->get_new()->result->access_token ?? null;

        $res = $tuya->devices($token)->get_details($this->cfg->get('params.device'))->result;

        $statuses = array_combine(
            array_column($res->status, 'code'),
            array_column($res->status, 'value')
        );
        $v = ($statuses['cur_voltage'] ?? 0) / 10;

        $status = match (true) {
            !$res->online => 0,
            $this->cfg->get('params.minV') > 0 && $this->cfg->get('params.minV') > $v => 2,
            $this->cfg->get('params.maxV') > 0 && $this->cfg->get('params.maxV') < $v => 3,
            default => 1
        };

        $this->cfg->set('status', $status);
        $this->cfg->set('voltage', $v);
    }
}
