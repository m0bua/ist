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

        $fields = $dev->class()::fields($dev, $fields);
        $suffixes = array_filter(array_unique(array_map(fn($i) =>
        trim($i->suffix ?? null), $fields)), fn($i) => !empty($i));
        $select = implode(', ', [
            "date",
            "data->'$.online' as online",
            'JSON_OBJECT(' . implode(', ', array_map(fn($i) =>
            "'$i->key', $i->sql", $fields)) . ') as fields'
        ]);

        $curWhere = $where = "t_id = " . $dev->get('address');

        $from = date_create($params['from'] ?? '-1day')->format('Y-m-d H:i');
        $to =  date_create($params['to'] ?? 'now')->format('Y-m-d H:i');
        $where .= " AND date >= '$from' AND date <= '$to'";
        $sql = "SELECT $select FROM tuya_log WHERE {where} ORDER BY date";
        $cur = DB::start()->one(strtr($sql, ['{where}' => $curWhere]) . ' DESC');
        if (empty($cur)) exit(header('location: /'));
        $f = json_decode($cur['fields'], true);
        $cur['fields'] = array_map(
            fn($i, $k) => (object)array_merge((array)$i, ['value' => $f[$k]]),
            $fields,
            array_keys($fields)
        );

        $entries = DB::start()->all(strtr($sql, ['{where}' => $where]));
        foreach ($fields as $f) {
            $title = ucfirst($f->key);
            if (count($suffixes) > 1 && isset($f->suffix)) $title .= " ($f->suffix)";
            $layers[] = (object)[
                'title' => $title,
                'data' => array_map(fn($i) =>
                [strtotime($i['date']), $i['online'] == 'true'
                    ? Helper::getArrayKey($i, "fields.$f->key") : 0], $entries)
            ];
        }

        $qChart = ['chart' => $dev->get('name')];

        foreach ($layers as $l) if (!empty($l->data)) {
            $on = date_create();
            $off = date_create();
            $vals = array_filter($l->data, fn($i) => $i[1] > 0);
            foreach ($l->data as $i) {
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
            }
            if (isset($dOn)) $on = ($on ?? date_create())->add($dOn->diff(date_create('@' . $i[0])));
            if (isset($dOff)) $off = ($off ?? date_create())->add($dOff->diff(date_create('@' . $i[0])));
            $onR = date_create()->diff($on ?? date_create())->format('%ad %H:%I');
            $offR = date_create()->diff($off ?? date_create())->format('%ad %H:%I');
            if (!empty($vals)) $ranges[] = (object)[
                'title' => $l->title,
                'min' => min(array_column($vals, '1')),
                'max' => max(array_column($vals, '1')),
                'on' => $onR == '0d 00:00' ? 0 : $onR,
                'off' => $offR == '0d 00:00' ? 0 : $offR,
            ];
        }

        return (object)[
            'dev' => $dev,
            'current' => (object)$cur,
            'from' => $from,
            'to' => $to,
            'ranges' => $ranges ?? [],
            'urls' => (object)[
                'buttons' => [
                    '24H' => http_build_query($qChart),
                    'Today' => http_build_query(array_merge($qChart, [
                        'from' => date_create()->setTime(0, 0)->format('Y-m-d H:i'),
                        'to' => date_create()->setTime(0, 0)->modify('+1 day')->format('Y-m-d H:i'),
                    ])),
                    'Week' => http_build_query(array_merge($qChart, [
                        'from' => date_create('last Monday')
                            ->setTime(0, 0)->format('Y-m-d H:i'),
                        'to' => date_create('last Monday')
                            ->setTime(0, 0)->modify('+1 week')->format('Y-m-d H:i'),
                    ])),
                    'Month' => http_build_query(array_merge($qChart, [
                        'from' => date_create('first day of this month')
                            ->setTime(0, 0)->format('Y-m-d H:i'),
                        'to' => date_create('first day of this month')
                            ->setTime(0, 0)->modify('+1 month')->format('Y-m-d H:i'),
                    ])),
                ],
                'back' => http_build_query(array_merge($qChart, [
                    'from' => date_create($from)->add(date_create($to)
                        ->diff(date_create($from)))->format('Y-m-d H:i'),
                    'to' => date_create($from)->format('Y-m-d H:i'),
                ])),
                'fwd' => http_build_query(array_merge($qChart, [
                    'from' => date_create($to)->format('Y-m-d H:i'),
                    'to' => date_create($to)->add(date_create($from)
                        ->diff(date_create($to)))->format('Y-m-d H:i'),
                ])),
            ],
            'chart' => (object)[
                'canvas' => '#chart canvas',
                'title' => str_replace('_', ' ', $dev->get('params.name', 'Chart')),
                'dataSuffix' => count($suffixes) === 1 ? reset($suffixes) : null,
                'dataType' => 'float',
                'layers' => $layers ?? [],
                'colors' => ['#4CAF50', '#FEB019', '#FF4560', '#008FFB', '#775DD0', '#00E396', '#546E7A']
            ],
        ];
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
}
