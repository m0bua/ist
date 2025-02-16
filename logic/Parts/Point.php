<?php

namespace Parts;

use Dev;

interface Point
{
    public static function init(Dev $cfg): self;
    public function check(array $data = []): void;
}
