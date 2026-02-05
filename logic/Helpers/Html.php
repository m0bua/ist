<?php

namespace Helpers;

use Auth, Dev;

class Html
{
    const CHART_CONFIGS = ['Tuya'];

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

    public static function getVoltageData(array $params, array $fields = [])
    {
        $dev = array_filter(Dev::all(), fn($i) => $i->get('name') == $params['chart']
            && in_array($i->get('class'), self::CHART_CONFIGS));
        if (empty($dev)) exit(header('location: /'));

        $dev = reset($dev);
        $field = $dev->get('params.voltage.field', 'voltage');
        $select = implode(', ', array_merge([
            "date",
            "data->'$.online' as online",
            "data->'$.status.$field'/10 as voltage"
        ], array_map(fn($i) => "data->'$.status.$i' as $i", $fields)));
        $curWhere = $where = "t_id = " . $dev->get('address');
        $from = date_create($params['from'] ?? '0:0')->format('Y-m-d H:i');
        $to =  date_create($params['to'] ?? '23:59')->format('Y-m-d H:i');
        $where .= " AND date >= '$from' AND date <= '$to'";
        $sql = "SELECT $select FROM tuya_log WHERE {where} ORDER BY date";
        $cur = DB::start()->one(strtr($sql, ['{where}' => $curWhere]) . ' DESC');
        $layers[] = ['hidden' => true];
        $entries = DB::start()->all(strtr($sql, ['{where}' => $where]));
        $layers[] = ['title' => 'Voltage', 'data' => array_map(fn($i) => [
            strtotime($i['date']),
            $i['online'] == 'true' ? $i['voltage'] : 0,
        ], $entries)];
        foreach ($fields as $f) $layers[] = [
            'title' => ucfirst($f),
            'data' => array_map(fn($i) =>
            [strtotime($i['date']), $i[$f]], $entries)
        ];

        $qChart = ['chart' => $dev->get('name')];
        [$f] = explode(' ', $from);
        [$t] = explode(' ', $to);

        return [
            'name' => str_replace('_', ' ', $dev->get('params.name')),
            'cfg' => $dev->get('name'),
            'from' => $from,
            'to' => $to,
            'current' => $cur,
            'color' => match (true) {
                $cur['online'] !== 'true' => 'red',
                $dev->get('params.voltage.min', $cur['voltage']) > $cur['voltage'] => 'yellow',
                $dev->get('params.voltage.max', $cur['voltage']) < $cur['voltage'] => 'blue',
                default => 'green',
            },
            'cfg' => $dev->get('name'),
            'urls' => [
                'now' => http_build_query($qChart),
                'back' => http_build_query(array_merge($qChart, [
                    'from' => date_create($f)->modify('-1 day')->setTime(0, 0)->format('Y-m-d H:i'),
                    'to' => date_create($f)->modify('-1 day')->setTime(23, 59)->format('Y-m-d H:i'),
                ])),
                'fwd' => http_build_query(array_merge($qChart, [
                    'from' => date_create($t)->modify('+1 day')->setTime(0, 0)->format('Y-m-d H:i'),
                    'to' => date_create($t)->modify('+1 day')->setTime(23, 59)->format('Y-m-d H:i'),
                ])),
            ],
            'chart' => [
                'canvas' => '#chart canvas',
                'title' => str_replace('_', ' ', $dev->get('params.name', 'Chart')),
                'dataSuffix' => empty($fields) ? 'V' : null,
                'dataType' => 'float',
                'layers' => $layers,
            ],
        ];
    }

    protected static function dataJsonRows(array $configs): array
    {
        $split = array_map(
            fn($i) => explode('_', $i),
            array_combine(array_keys($configs), array_keys($configs))
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
}
