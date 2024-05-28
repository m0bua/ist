<?php
class Helper
{
    private const DEFAULT_WAIT = '180';
    private static ?DateTimeZone $tz = null;

    public static function getDataJson(): string
    {
        $configs = Config::all();
        $result = [['tag' => 'br'], ['tag' => 'table', 'children' => [
            ['tag' => 'tr', 'children' => [
                ['tag' => 'th', 'params' => ['colspan' => 2], 'text' => 'Device'],
                ['tag' => 'th', 'text' => 'Status'],
                ['tag' => 'th', 'text' => 'Last change'],
                ['tag' => 'th', 'text' => 'Last On'],
                ['tag' => 'th', 'text' => 'Last Off'],
            ]]
        ]]];

        self::getDataJsonRow($configs, $result[count($result) - 1]['children'], 2);

        return json_encode($result);
    }

    public static function after(?string $date, bool $withPref = false): string
    {
        $result = '';
        if (!empty($date)) {
            $result = date_create($date)
                ->diff(date_create())
                ->format('%r%ad %H:%I:%S');
            if ($withPref) $result = ' after ' . $result;
        }

        return $result;
    }

    public static function changed(Config $cfg): bool
    {
        $date = $cfg->get((int)$cfg->get('current'));
        if (empty($date)) return false;

        $wait = empty($_GET['t'])
            ? $cfg->get('wait', self::DEFAULT_WAIT)
            : $_GET['t'] + 1;

        return date_create("-$wait sec") < date_create($date);
    }

    public static function dateFormat(?string $date): string
    {
        if (empty($date)) return '';

        $date = date_create($date);
        if (!empty(self::tz())) $date->setTimezone(self::tz());

        return $date->format('Y.m.d H:i');
    }

    public static function tz(): ?DateTimeZone
    {
        $tzList = timezone_identifiers_list();
        if (isset($_GET['tz']) && in_array($_GET['tz'], $tzList))
            self::$tz = new DateTimeZone($_GET['tz']);

        return self::$tz;
    }

    public static function array(&$array, $key, $value)
    {
        if (is_null($key)) return $array = $value;
        $keys = explode('_', $key);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key]) || !is_array($array[$key]))
                $array[$key] = [];
            $array = &$array[$key];
        }
        $array[array_shift($keys)] = $value;

        return $array;
    }

    private static function getDataJsonRow(array $configs, array &$result, int $span = 1)
    {
        foreach ($configs as $dev => $cfg) {
            if (is_array($cfg)) {
                $result[] = ['tag' => 'tr', 'children' => [
                    ['tag' => 'td', 'params' => [
                        'rowspan' => count($cfg) + 1,
                    ], 'text' => strtoupper($dev)],
                ]];
                self::getDataJsonRow($cfg, $result);
            } else {
                $status = $cfg->get('current');
                $upd = self::changed($cfg);
                $result[] = ['tag' => 'tr', 'children' => [
                    ['tag' => 'td', 'params' => ['colspan' => $span], 'text' => strtoupper($dev)],
                    ['tag' => 'td', 'upd' => $upd, 'params' => [
                        'style' => ['color' => $status ? 'green' : 'red']
                    ], 'text' => $status ? 'On' : 'Off'],
                    ['tag' => 'td', 'upd' => true, 'text' => self::after($cfg->get((int)$status))],
                    ['tag' => 'td', 'upd' => $upd, 'text' => self::dateFormat($cfg->get(1))],
                    ['tag' => 'td', 'upd' => $upd, 'text' => self::dateFormat($cfg->get(0))],
                ]];
            }
        }
    }

    private static function arrayMaxDepth(array $array): int
    {
        $depth = 1;
        foreach ($array as $value) {
            if (is_array($value)) {
                $depth += self::arrayMaxDepth($value);
                break;
            }
        }

        return $depth;
    }
}
