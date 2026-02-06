<?php

namespace Points;

use Helpers\TuyaApi;

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
        $res = TuyaApi::get($this->cfg->get('address'), $this->cfg->get('wait'));
        if (empty($res)) return;

        $s = $this->cfg->get('status');
        $vp = json_decode($this->cfg->get('params.voltage', '{}'));
        $field = $vp->field ?? 'voltage';
        $v = $res->online ? ($res->status->$field ?? 0) / 10 : 0;
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
