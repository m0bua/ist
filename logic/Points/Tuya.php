<?php

namespace Points;

use Helpers\TuyaApi;

use Dev;
use Helpers\{Helper, Msg};
use Parts\Point;

class Tuya implements Point
{
    const STATUSES = ['off', 'on', 'low', 'high'];
    const COLORS = ['red', 'green', 'goldenrod', 'blue'];

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
        $min = $this->cfg->get('params.voltage.min');
        $max = $this->cfg->get('params.voltage.max');
        $back = $this->cfg->get('params.voltage.back', 1);
        $field = $this->cfg->get('params.voltage.field', 'voltage');
        if (is_array($field)) $field = reset($field);
        $v = $res->online ? ($res->status->$field ?? 0) / 10 : 0;
        $sCnt = $this->cfg->statusesCnt();
        $this->status = match (true) {
            !$res->online => 0,
            $v > 0 && $min && $sCnt >= 3 && $min > $v => 2,
            $v > 0 && $max && $sCnt >= 4 && $max < $v => 3,
            $s == 0 ||
                ($min && $s == 2 && ($min + $back) < $v) ||
                ($max && $s == 3 && ($max - $back) > $v) => 1,
            default => $s
        };

        $this->cfg->set('status', $this->status);
        $this->cfg->set((int)$this->status, Helper::date());
        $this->cfg->set('v', $v);
    }

    public static function fields(Dev $dev, array $fields = [])
    {
        $field = $dev->get('params.voltage.field', 'voltage');
        $fields = array_merge(is_array($field) ? $field : [$field], $fields);
        $fields = array_map(function ($k, $i) {
            if (is_array($i)) {
                $key = $i['key'] ?? $i['name'] ?? null;
                $result = (object)[
                    'key' => $i['key'] ?? $key,
                    'sql' => $i['sql'] ?? "data->'$.status.$key",
                ];
                if (isset($i['name'])) $result->name = $i['name'];
                if (isset($i['suffix'])) $result->suffix = $i['suffix'];
                elseif (empty($result->name)) $result->name = ucfirst($key);
            } else {
                $key = is_string($k) ? $k : $i;
                $result = (object)[
                    'key' => $key,
                    'name' => ucfirst($key),
                    'sql' => "data->'$.status.$key'",
                ];
            }

            return $result;
        }, array_keys($fields), $fields);

        return array_combine(array_column($fields, 'key'), $fields);
    }
}
