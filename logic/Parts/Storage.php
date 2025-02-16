<?php

namespace Parts;

use Helpers\DB;

trait Storage
{
    protected array $data = [];
    protected array $orig = [];

    function __destruct()
    {
        $this->save();
    }

    protected function save(): void
    {
        if ($this->data !== $this->orig)
            DB::start()->upsert(static::TABLE, $this->data);
    }

    protected function setData(array $data): void
    {
        $this->data = $data;
        $this->orig = $data;
    }
}
