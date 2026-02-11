<?php

namespace Helpers;

use DateTimeZone;
use Parts\Cfg;

class Helper
{
    private const DEFAULT_WAIT = '300';
    private static DateTimeZone $tz;

    public static function after(?string $date, ?string $format = null): string
    {
        if (empty($date)) return '';
        $diff = date_create($date)->diff(date_create());
        $strtr['{th}'] = $diff->days * 24 + $diff->h;
        $strtr['{tm}'] = $strtr['{th}'] * 60 + $diff->m;
        $strtr['{ts}'] = $strtr['{tm}'] * 60 + $diff->s;
        $format = empty($format)
            ? '%ad %H:%I:%S'
            :  strtr($format, $strtr);

        return date_create($date)
            ->diff(date_create())
            ->format($format);
    }

    public static function changed(Cfg $cfg, ?string $field = null): bool
    {
        $date = $cfg->get($field ?? $cfg->get('status'));
        if (empty($date)) return true;

        $wait = $cfg->get('params.wait', self::DEFAULT_WAIT);

        return date_create("-$wait sec") > date_create($date);
    }

    public static function dateFormat(?string $date, ?string $format = null): string
    {
        if (empty($date)) return '';

        $date = date_create($date);
        if (!empty(self::tz())) $date->setTimezone(self::tz());

        return $date->format($format ?? 'Y.m.d H:i');
    }

    public static function tz(): ?DateTimeZone
    {
        $tzList = timezone_identifiers_list();
        if (isset($_GET['tz']) && in_array($_GET['tz'], $tzList))
            self::$tz = new DateTimeZone($_GET['tz']);

        return self::$tz ?? null;
    }

    public static function getArrayKey(
        $data,
        ?string $field = null,
        $default = null
    ) {
        if (is_null($field)) return $data;
        $item = self::getKey($data, $field);
        if ($item != $data) $data = $item;
        else foreach (explode('.', $field) as $segment) {
            $item = self::getKey($data, $segment);
            if ($item != $data) $data = $item;
            else return $default;
        }

        if (is_numeric($data))
            $data = ceil($data) == $data
                ? (int)$data : (float)$data;
        if (is_string($data))
            $data = @json_decode($data) ?? $data;

        return $data;
    }

    public static function getKey($item, $key)
    {
        $data = $item;
        if (is_string($data)) $data = json_decode($data, true) ?? $data;
        if (is_array($data) && isset($data[$key])) $item = $data[$key];
        elseif (is_object($data) && isset($data->$key)) $item = $data->$key;

        return $item;
    }

    public static function pluck(array $array, $key = null, mixed $value = null)
    {
        $keys = empty($key)
            ? (is_array($array) ? array_keys($array) : $array)
            : (is_array($key) ? $key : array_map(fn($i)
            => self::getArrayKey($i, $key), $array));

        $values = empty($value) ? $array
            : (is_array($value) ? $value : array_map(fn($i)
            => self::getArrayKey($i, $value), $array));

        return array_combine($keys, (array)$values);
    }

    public static function ip(): string
    {
        return $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '';
    }

    public static function date(string $datetime = 'now'): string
    {
        return date_create($datetime)->format('Y-m-d H:i:s');
    }

    public static function phpExec(string $cmd = '', string $params = ''): string
    {
        $ds = DIRECTORY_SEPARATOR;
        $ver = implode('.', array_slice(explode('.', phpversion()), 0, 2));
        $path = explode(':', $_SERVER['PATH']);
        $path = array_filter(array_merge(
            array_map(fn($i) => "$i/php$ver", $path),
            array_map(fn($i) => "$i/php", $path),
        ), fn($i) => is_file($i));

        $path = array_shift($path);
        if (!empty($cmd)) $path .= ' ' . implode($ds, [ROOT, $cmd]);

        if (!empty($params)) $path .= " $params";

        return $path;
    }
}
