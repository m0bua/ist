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

        if ($this->get(2, false)) {
            $model = DB::start()->one("SELECT * FROM points_log
                WHERE status=2 AND point_id=" . $this->get('id'));
            $model['date'] = $this->get(2);
            DB::start()->upsert(static::TABLE . '_log', $model);
        }

        $this->save();
    }

    public function change(string $field, $val = null): bool
    {
        if (is_bool($this->get($field)) || $field === 'active')
            $this->set($field, (int)!$this->get($field));
        else $this->set($field, $val);

        return true;
    }

    public function getOrig(?string $field = null, $default = null): array
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

    public function dev(): string
    {
        return strtoupper($this->get('name', ''));
    }

    protected static function sort(array &$data): void
    {
        ksort($data, SORT_STRING);
        foreach ($data as $item)
            if (is_array($item)) self::sort($item);
    }
}
