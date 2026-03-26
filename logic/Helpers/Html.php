<?php

namespace Helpers;

use Auth, Dev, stdClass, DateTime;

class Html
{
    const CHART_CONFIGS = ['Tuya'];
    const CHART_COLORS = [
        '#4CAF50',
        '#FEB019',
        '#FF4560',
        '#008FFB',
        '#775DD0',
        '#00E396',
        '#546E7A',
    ];
    const DATE_FORMAT = 'Y-m-d H:i';

    public static function getClientsJson(): string
    {
        foreach (Dev::all() as $item) $configs[$item->name()] = $item;
        if (empty($configs)) return json_encode([]);
        ksort($configs);

        $maxKeys = max(array_map(fn($i) => count(explode('_', $i)), array_keys($configs)));
        $children = [
            ['tag' => 'th', 'params' => ['colspan' => $maxKeys], 'text' => 'Device'],
            ['tag' => 'th', 'text' => 'Status'],
            ['tag' => 'th', 'text' => 'Last change'],
            ['tag' => 'th', 'text' => 'Last On'],
            ['tag' => 'th', 'text' => 'Last Off'],
        ];
        if (!empty(Auth::clients())) $children[] = ['tag' => 'th', 'text' => 'Update'];
        $response = json_encode([['tag' => 'br'], ['tag' => 'table', 'children' => array_merge([
            ['tag' => 'tr', 'children' => $children]
        ], self::dataJsonRows($configs))]]);

        header('Content-Type: application/json; charset=utf-8');
        return $response;
    }

    public static function getTData(array $params)
    {
        $dev = array_filter(Dev::all(), fn($i) => $i->get('name') == $params['chart']
            && in_array($i->get('class'), self::CHART_CONFIGS));
        if (empty($dev)) Helper::redirect();
        $dev = reset($dev);

        $fields = $dev->class()::fields($dev);
        $select = implode(', ', [
            'date',
            "data->'$.online' as online",
            'JSON_OBJECT(' . implode(', ', array_map(
                fn($i) => "'$i->key', $i->sql",
                call_user_func_array('array_merge', array_values($fields))
            )) . ') as fields'
        ]);
        $where = $curWhere = "t_id = " . $dev->get('address');

        preg_match('/([A-z]*)([+ -]?)(\d*)/', $params['preset'] ?? '', $preset);
        [$from, $to] = match ($preset[1] ?? null) {
            'hour' => [
                date_create()->modify('-1hour+1min'),
                date_create()->modify('+1min')
            ],
            'today' => [
                date_create()->setTime(0, 0),
                date_create()->setTime(0, 0)->modify('+1day')
            ],
            'week' => [
                date_create('monday this week')->setTime(0, 0),
                date_create('next monday')->setTime(0, 0)
            ],
            'month' => [
                date_create('first day of this month')->setTime(0, 0),
                date_create('first day of next month')->setTime(0, 0)
            ],
            default => [
                date_create($params['from'] ?? '-1day+1min'),
                date_create($params['to'] ?? '+1min')
            ],
        };

        if (is_numeric($preset[3])) {
            [$diff, $count] = [$preset[2] == '-' ? $to->diff($from) : $from->diff($to), $preset[3]];
            while ($count-- > 0) [$from, $to] = [$from->add($diff), $to->add($diff)];
            $preset = [$preset[1], $preset[2] == '-' ? 0 - $preset[3] : $preset[3]];
        } else $preset = [$preset[1] ?? '', 0];

        $where .= strtr(' AND date >= "$from" AND date <= "$to"', [
            '$from' => $from->format(self::DATE_FORMAT),
            '$to' => $to->format(self::DATE_FORMAT),
        ]);
        $sql = "SELECT $select FROM tuya_log WHERE {where} ORDER BY date";

        $entries = DB::start()->all(strtr($sql, ['{where}' => $where]));
        $cur = DB::start()->one(strtr($sql, ['{where}' => $curWhere]) . ' DESC');
        if (empty($cur)) Helper::redirect();
        $field = json_decode($cur['fields'], true);
        $cur['fields'] = array_map(fn($i) => array_map(fn($i, $k) =>
        (object)array_merge((array)$i, ['value' =>
        $field[$k]]), $i, array_keys($i)), $fields);

        foreach ($fields as $fKey => $fArray) {
            $layers = [];
            $ranges = [];
            $suffixes = [];
            $colors = self::CHART_COLORS;
            foreach (array_values($fArray) as $key => $field) {
                $title = ucfirst($field->name ?? $field->key);
                $suffixes[] = $field->suffix;
                $data = array_map(fn($i) => [strtotime($i['date']), $i['online'] == 'true'
                    ? Helper::getArrayKey($i, "fields.$field->key") : 0], $entries);
                $on = date_create();
                $off = date_create();
                foreach ($data ?? [] as $i)
                    if ($i[1] > 0) {
                        if (empty($dOn)) $dOn = date_create('@' . $i[0]);
                        if (isset($dOff)) $off = ($off ?? date_create())
                            ->add($dOff->diff(date_create('@' . $i[0])));
                        unset($dOff);
                    } else {
                        if (empty($dOff)) $dOff = date_create('@' . $i[0]);
                        if (isset($dOn)) $on = ($on ?? date_create())
                            ->add($dOn->diff(date_create('@' . $i[0])));
                        unset($dOn);
                    }
                if (isset($dOn)) $on = ($on ?? date_create())
                    ->add($dOn->diff(date_create('@' . $i[0])));
                if (isset($dOff)) $off = ($off ?? date_create())
                    ->add($dOff->diff(date_create('@' . $i[0])));
                $onR = date_create()->diff($on ?? date_create())
                    ->format('%ad %H:%I');
                $offR = date_create()->diff($off ?? date_create())
                    ->format('%ad %H:%I');
                $vals = array_filter($data, fn($i) => $i[1] > 0);
                if (!empty($vals)) $ranges[] = (object)[
                    'key' => $key,
                    'title' => $title,
                    'min' => min(array_column($vals, '1')),
                    'max' => max(array_column($vals, '1')),
                    'on' => $onR == '0d 00:00' ? 0 : $onR,
                    'off' => $offR == '0d 00:00' ? 0 : $offR,
                    'count' => count($data),
                ];

                if (isset($field->color)) $colors[$key] = $field->color;
                $layers[] = (object)(self::skip("show_{$fKey}_{$key}")
                    ? ['title' => $title] : ['title' => $title, 'data' => $data]);
            }

            $min = empty($ranges) ? 0 : min(array_column($ranges, 'min'));
            $max = empty($ranges) ? 0 : max(array_column($ranges, 'max'));
            if ($min < 0) $min = 0;
            $k = implode('_', array_column($fArray, 'key'));
            $title = str_replace('_', ' ', $dev->get('params.name', 'Chart'));
            if (!is_int($fKey)) $title .= " $fKey";
            $charts[$k] = (object)['key' => $fKey, 'ranges' => $ranges, 'chart' => (object)[
                'canvas' => "#chart_$k canvas",
                'title' => $title,
                'dataSuffix' => count(array_unique($suffixes)) === 1
                    ? reset($suffixes) : null,
                'dataType' => 'float',
                'zoom' => ['yMin' => $min, 'yMax' => $max],
                'zeroFloor' => false,
                'autoHeadroom' => false,
                'layers' => $layers,
                'colors' => $colors,
            ]];
        }

        return (object)self::chartsData($dev, $cur, $charts, $from, $to, $preset);
    }

    public static function skip($key = null)
    {
        $cookie = explode('||', $_COOKIE['toggle_inputs']);

        return empty($key) ? $cookie : in_array($key, $cookie);
    }

    protected static function dataJsonRows(array $configs): array
    {
        $split = array_map(
            fn($i) => explode('_', $i),
            Helper::pluck($configs, null, array_keys($configs))
        );
        $max = max(array_map(fn($i) => count($i), $split));
        foreach ($configs as $dev => $cfg) {
            $status = $cfg->get('status');
            $children = [];
            foreach ($split[$dev] as $k => $name) {
                $same = array_filter($split, function ($item) use ($split, $dev, $k) {
                    for ($i = 0; $i <= $k; $i++) $res = ($res ?? true)
                        && (($item[$i] ?? '') === $split[$dev][$i]);
                    return $res ?? false;
                });
                $blockMax = max(array_map(fn($i) => count($i), $same));
                $name = strtoupper($name);

                if (
                    in_array($cfg->get('class'), self::CHART_CONFIGS)
                    && $k + 1 == $blockMax
                ) $name = [[
                    'tag' => 'a',
                    'text' => $name,
                    'params' => ['href' => '?chart=' . $cfg->get('name')]
                ]];
                else $name = [['tag' => 'span', 'text' => $name]];

                if (array_search($dev, array_keys($same)) === 0)
                    $children[] = ['tag' => 'td', 'children' => $name, 'params' => [
                        'rowspan' => count($same),
                        'colspan' => count($split[$dev]) === $blockMax
                            && $k === array_key_last($split[$dev])
                            ? $max + 1 - count($split[$dev]) : 1,
                    ]];
            }

            if (count($split[$dev]) < $blockMax)
                $children[] = ['tag' => 'td', 'params' => [
                    'rowspan' => 1,
                    'colspan' => $max - count($split[$dev]),
                ], 'text' => null];

            $row = ['tag' => 'tr', 'children' => array_merge($children, [
                [
                    'tag' => 'td',
                    'params' => [
                        'style' => ['color' => match ($status) {
                            0 => 'red',
                            2, 3 => 'yellow',
                            default => 'green',
                        }]
                    ],
                    'text' => match ($status) {
                        0 => 'Off',
                        2 => 'Low',
                        3 => 'High',
                        default => 'On',
                    }
                ],
                ['tag' => 'td', 'text' => Helper::after(
                    $cfg->get('dates.' . $status),
                    $cfg->get('params.dateDiffFormat')
                )],
                ['tag' => 'td', 'text' => Helper::dateFormat(
                    $cfg->get('dates.1'),
                    $cfg->get('params.dateFormat')
                )],
                ['tag' => 'td', 'text' => Helper::dateFormat(
                    $cfg->get('dates.0'),
                    $cfg->get('params.dateFormat')
                )],
            ])];

            if (Auth::client($cfg->get('name'), true))
                $row['children'][] = ['tag' => 'td', 'children' => [[
                    'tag' => 'button',
                    'text' => $cfg->get('active') ? 'On' : 'Off',
                    'params' => [
                        'id' => $cfg->get('name'),
                        'class' => 'isActive',
                        'style' => ['background' => $cfg->get('active') ? 'green' : 'red']
                    ]
                ]]];

            $result[] = $row;
        }

        return $result ?? [];
    }

    protected static function chartsData(
        Dev $dev,
        array $cur,
        array $charts,
        DateTime $from,
        DateTime $to,
        array $preset
    ): stdClass {
        $query = ['chart' => $dev->get('name')];
        $back = $fwd = $preset;
        if (!$back[0]) unset($back[0]);
        if (!--$back[1]) unset($back[1]);
        elseif ($back[1] > 0) $back[1] = " $back[1]";
        if (!$fwd[0]) unset($fwd[0]);
        if (!++$fwd[1]) unset($fwd[1]);
        elseif ($fwd[1] > 0) $fwd[1] = " $fwd[1]";
        return (object)[
            'dev' => $dev,
            'current' => (object)$cur,
            'from' => $from->format(self::DATE_FORMAT),
            'to' => $to->format(self::DATE_FORMAT),
            'charts' => $charts,
            'urls' => (object)[
                'buttons' => [
                    'Default' => http_build_query($query),
                    'Hour' => http_build_query(array_merge($query, ['preset' => 'hour'])),
                    'Today' => http_build_query(array_merge($query, ['preset' => 'today'])),
                    'Week' => http_build_query(array_merge($query, ['preset' => 'week'])),
                    'Month' => http_build_query(array_merge($query, ['preset' => 'month'])),
                ],
                'back' => http_build_query(array_merge($query, empty($back) ? [] : ['preset' => implode($back)])),
                'fwd' => http_build_query(array_merge($query, empty($fwd) ? [] : ['preset' => implode($fwd)])),
            ],
        ];
    }
}
