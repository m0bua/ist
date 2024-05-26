<?php
class Helper
{
    private static ?DateTimeZone $tz = null;

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

    public static function msgPrepare(string $pattern, array $params): string
    {
        preg_match_all('/{[^}]+}/', $pattern, $matches);
        foreach (reset($matches) as $item) {
            $trim = trim($item, '{}');
            if (strpos($trim, ':')) {
                [$key, $field] = explode(':', $trim);
                if (strpos($field, '#'))
                    $field = empty($params[$key]) ? '' : str_replace('#', $params[$key], $field);
                elseif (strpos($field, '|'))
                    $field = explode('|', $field)[$params[$key]];

                $pattern = str_replace($item, $field, $pattern);
            } else {
                $pattern = str_replace($item, $params[$trim] ?? '', $pattern);
            }
        }

        return $pattern;
    }

    public static function dateFormat(?string $date): string
    {
        return empty($date) ? ''
            : date_create($date)
            ->setTimezone(self::tz())
            ->format('Y.m.d H:i');
    }

    public static function getDataJson(): string
    {
        $result = [['tag' => 'br'], ['tag' => 'table', 'children' => [
            ['tag' => 'tr', 'children' => [
                ['tag' => 'th', 'params' => ['colspan' => 2], 'text' => 'Device'],
                ['tag' => 'th', 'text' => 'Status'],
                ['tag' => 'th', 'text' => 'Last change'],
                ['tag' => 'th', 'text' => 'Last On'],
                ['tag' => 'th', 'text' => 'Last Off'],
            ]]
        ]]];

        self::getDataJsonRow(Config::all(), $result[count($result) - 1]['children'], 2);

        return json_encode($result);
    }

    private static function getDataJsonRow(array $confis, array &$result, int $span = 1)
    {
        foreach ($confis as $dev => $cfg) {
            if (is_array($cfg)) {
                $result[] = ['tag' => 'tr', 'children' => [
                    ['tag' => 'td', 'params' => ['rowspan' => count($cfg) + 1], 'text' => strtoupper($dev)],
                ]];
                self::getDataJsonRow($cfg, $result);
            } else {
                $status = $cfg->get('current');
                $result[] = ['tag' => 'tr', 'children' => [
                    ['tag' => 'td', 'params' => ['colspan' => $span], 'text' => strtoupper($dev)],
                    ['tag' => 'td', 'upd' => true, 'params' => [
                        'style' => ['color' => $status ? 'green' : 'red']
                    ], 'text' => $status ? 'On' : 'Off'],
                    ['tag' => 'td', 'upd' => true, 'text' => Helper::after($cfg->get((int)$status))],
                    ['tag' => 'td', 'upd' => true, 'text' => Helper::dateFormat($cfg->get(1))],
                    ['tag' => 'td', 'upd' => true, 'text' => Helper::dateFormat($cfg->get(0))],
                ]];
            }
        }
    }

    public static function tz(): ?DateTimeZone
    {
        if (isset($_GET['tz'])) self::$tz = new DateTimeZone($_GET['tz']);

        return self::$tz;
    }
}
