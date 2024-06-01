<?php
class Helper
{
    private const DEFAULT_WAIT = '180';
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

    public static function getDataJson(): string
    {
        $configs = Config::all();
        $maxKeys = max(array_map(fn ($i) => count(explode('_', $i)), array_keys($configs)));
        return json_encode([['tag' => 'br'], ['tag' => 'table', 'children' => array_merge([
            ['tag' => 'tr', 'children' => [
                ['tag' => 'th', 'params' => ['colspan' => $maxKeys], 'text' => 'Device'],
                ['tag' => 'th', 'text' => 'Status'],
                ['tag' => 'th', 'text' => 'Last change'],
                ['tag' => 'th', 'text' => 'Last On'],
                ['tag' => 'th', 'text' => 'Last Off'],
            ]]
        ], self::dataJsonRows($configs))]]);
    }

    private static function dataJsonRows(array $configs): array
    {
        $split = array_map(
            fn ($i) => explode('_', $i),
            array_combine(array_keys($configs), array_keys($configs))
        );

        foreach ($configs as $dev => $cfg) {
            $status = $cfg->get('current');
            $upd = self::changed($cfg);
            $children = [];
            foreach ($split[$dev] as $k => $name) {
                $same = array_filter($split, function ($item) use ($split, $dev, $k) {
                    for ($i = 0; $i <= $k; $i++) $result = ($result ?? true)
                        && ($item[$i] === $split[$dev][$i]);
                    return $result ?? false;
                });
                if (array_search($dev, array_keys($same)) === 0)
                    $children[] = ['tag' => 'td', 'params' => [
                        'rowspan' => count($same),
                        'colspan' => $k === array_key_last($split[$dev])
                            ? max(array_map(fn ($i) => count($i), $split))
                            - count($split[$dev]) + 1 : 1,
                    ], 'text' => strtoupper($name)];
            }
            $row = ['tag' => 'tr', 'children' => array_merge($children, [
                ['tag' => 'td', 'upd' => $upd, 'params' => [
                    'style' => ['color' => $status ? 'green' : 'red']
                ], 'text' => $status ? 'On' : 'Off'],
                ['tag' => 'td', 'upd' => true, 'text' => self::after($cfg->get((int)$status))],
                ['tag' => 'td', 'upd' => $upd, 'text' => self::dateFormat($cfg->get(1))],
                ['tag' => 'td', 'upd' => $upd, 'text' => self::dateFormat($cfg->get(0))],
            ])];

            $row['children'][] = ['tag' => 'td', 'upd' => $upd, 'children' => [[
                'tag' => 'button', 'text' => $cfg->get('active') ? 'On' : 'Off',
                'params' => [
                    'id' => $cfg->name(), 'class' => 'isActive',
                    'style' => ['background' => $cfg->get('active') ? 'green' : 'red']
                ]
            ]]];

            $result[] = $row;
        }

        return $result ?? [];
    }
}
