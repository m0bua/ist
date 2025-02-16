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
        $date = $cfg->get($field ?? ('dates.' . $cfg->get('status')));
        if (empty($date)) return true;

        $wait = empty($_GET['t'])
            ? $cfg->get('params.wait', self::DEFAULT_WAIT)
            : $_GET['t'] + 1;

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
        $array,
        ?string $field = null,
        $default = null
    ) {
        if (is_null($field)) return $array;
        if (isset($array[$field])) return $array[$field];
        foreach (explode('.', $field) as $segment) {
            if (is_string($array))
                $array = json_decode($array, true) ?? $array;
            if (
                !is_array($array) ||
                !array_key_exists($segment, $array)
            ) return $default;
            $array = $array[$segment];
        }
        return $array;
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
