<?php

namespace Points;

use tuyapiphp\TuyaApi;

use Dev;
use Helpers\{Helper, Msg};
use Parts\Point;

class Tuya implements Point
{
    protected Dev $cfg;
    protected int $status;

    public function check(array $data = []): void
    {
        echo "### " . $this->cfg->name() . "\n";
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
            'accessKey' => $this->cfg->get('params.cli.id'),
            'secretKey' => $this->cfg->get('params.cli.secret'),
            'baseUrl' => $this->cfg->get('address')
        ]);
        $token = $tuya->token->get_new()->result->access_token ?? null;
        $res = $tuya->devices($token)
            ->get_details($this->cfg->get('params.cli.dev'))->result;
        $valArr = array_combine(
            array_column($res->status, 'code'),
            array_column($res->status, 'value')
        );

        $v = $res->online ? ($valArr['cur_voltage'] ?? 0) / 10 : 0;
        $s = $this->cfg->get('status');
        $vp = json_decode($this->cfg->get('params.voltage', '{}'));
        $sCnt = $this->cfg->statusesCnt();
        $this->status = match (true) {
            !$res->online => 0,
            isset($vp->min) && $sCnt >= 3 && $vp->min > $v => 2,
            isset($vp->max) && $sCnt >= 4 && $vp->max < $v => 3,
            $s == 0 ||
                (isset($vp->min) && $s == 2 && ($vp->min + ($vp->back ?? 1)) < $v) ||
                (isset($vp->max) && $s == 3 && ($vp->max - ($vp->back ?? 1)) > $v) => 1,
            default => $s
        };

        $this->cfg->set('status', $this->status);
        $this->cfg->set((int)$this->status, Helper::date());
        $this->cfg->set('v', $v);
    }
}
