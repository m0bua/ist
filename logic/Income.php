<?php

use Helpers\{Helper, Msg};
use Parts\Point;

class Income implements Point
{
    protected Dev $cfg;

    public function check(array $data = []): void
    {
        if ($data) {
            $this->cfg->set(2, Helper::date());
            $this->cfg->set('address', Helper::ip());
            if(isset($data['m']))
                $this->cfg->set('message', $data['m']);
            if (!$this->cfg->get('status')) {
                $this->cfg->set('status', 1);
                $this->cfg->set(1, Helper::date());
            }
        } else {
            echo "### " . $this->cfg->dev() . "\n";
            if (
                $this->cfg->get('status') &&
                Helper::changed($this->cfg, 'dates.2')
            ) {
                $this->cfg->set('status', 0);
                $this->cfg->set(0, Helper::date());
            }
        }

        (new Msg)->send($this->cfg);
        $this->cfg->unset('message');
    }

    public static function init(Dev $cfg): self
    {
        $self = new self;
        $self->cfg = $cfg;

        return $self;
    }
}
