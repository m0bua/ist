<?php

namespace Parts;

use Helpers\{DB, Helper};

abstract class Cfg
{
    use Storage;

    function __construct(array $data)
    {
        $this->setData($data);
    }

    function __destruct()
    {
        foreach ([0, 1] as $i) if (
            $this->get($i, false) &&
            $i !== $this->getOrig('status')
        ) DB::start()->upsert(static::TABLE . '_log', [
            'point_id' => $this->get('id'),
            'status' => $i,
            'date' => $this->get($i),
        ]);

        $this->save();
    }

    public function change(string $field, $val = null): bool
    {
        if (is_bool($this->get($field)) || $field === 'active')
            $this->set($field, (int)!$this->get($field));
        else $this->set($field, $val);

        return true;
    }

    public function getOrig(?string $field = null, $default = null)
    {
        return empty($field) ? $this->orig : $this->orig[$field] ?? $default;
    }

    public function get(?string $field = null, $default = null)
    {
        return Helper::getArrayKey($this->data, $field, $default);
    }

    public function set(string $field, $value): void
    {
        $array = &$this->data;
        foreach (explode('.', $field) as $segment)
            $array = &$array[$segment];
        $array = $value;
    }

    public function unset(string $field): void
    {
        if (isset($this->data[$field]))
            unset($this->data[$field]);
    }

    public function changed(string $field)
    {
        $orig = $this->orig[$field] ?? null;
        $new = $this->data[$field] ?? null;

        return !($orig == $new);
    }

    public function name(): string
    {
        return $this->get('params.name', false) ?:
            strtoupper($this->get('name', ''));
    }

    protected static function sort(array &$data): void
    {
        ksort($data, SORT_STRING);
        foreach ($data as $item)
            if (is_array($item)) self::sort($item);
    }
}
