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

    private Dev $dev;
    private DateTime $from;
    private DateTime $to;
    private array $preset = [];

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

        return $response;
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

    public static function getChartData(array $params)
    {
        return (new self)->prepareChartsData($params);
    }

    private function prepareChartsData($params)
    {
        $dev = array_filter(Dev::all(), fn($i) => $i->get('name') == $params['chart']
            && in_array($i->get('class'), self::CHART_CONFIGS));
        if (empty($dev)) Helper::redirect();

        $this->dev = reset($dev);

        $fields = $this->dev->class()::fields($this->dev);
        $select = implode(', ', [
            'date',
            "data->'$.online' as online",
            'JSON_OBJECT(' . implode(', ', array_map(
                fn($i) => "'$i->key', $i->sql",
                call_user_func_array('array_merge', array_values($fields))
            )) . ') as fields'
        ]);
        $where = 't_id = :tId';
        $wParams = [':tId' => $this->dev->get('address')];

        $sql = "SELECT $select FROM tuya_log WHERE {where} ORDER BY date";
        $cur = DB::start()->one(strtr($sql, ['{where}' => $where]) . ' DESC', $wParams);

        $this->dateFromPreset($params);

        $where .= ' AND date >= :from AND date <= :to';
        $wParams[':from'] = $this->from->format(self::DATE_FORMAT);
        $wParams[':to'] = $this->to->format(self::DATE_FORMAT);

        $entries = DB::start()->all(strtr($sql, ['{where}' => $where]), $wParams);
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
                $suffixes[] = $field->suffix ?? '';
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
            $title = str_replace('_', ' ', $this->dev->get('params.name', 'Chart'));
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

        return (object)$this->chartsDataFinalArray($cur, $charts);
    }

    public static function skip($key = null)
    {
        $cookie = explode('||', $_COOKIE['toggle_inputs'] ?? '');

        return empty($key) ? $cookie : in_array($key, $cookie);
    }

    protected function dateFromPreset(array $params): void
    {
        preg_match('/^([-\d.]*)(\*?)(clean-|)([A-z]*)([-\s\d.]*)$/', $params['preset'] ?? '', $this->preset);
        unset($this->preset[0]);
        $date = date_create('1min');
        $this->preset[1] = abs((float)$this->preset[1]);
        if (empty($this->preset[1])) $this->preset[1] = 1;
        $this->preset[5] = (float)$this->preset[5];
        $mul = -$this->preset[1];
        if (empty($this->preset[4]))
            $this->preset[4] = 'day';
        if (!empty($this->preset[3])) {
            if ($mul < 0) $date->modify("1 {$this->preset[4]}");
            match ($this->preset[4] ?? null) {
                'hour' => $date->setTime($date->format('H'), 0, 0),
                'day' => $date->modify('today'),
                'week' => $date->modify('monday this week 00:00:00'),
                'month' => $date->modify('first day of this month 00:00:00'),
            };
        }
        $mDate = (clone $date)->modify("$mul {$this->preset[4]}");
        if (is_numeric($this->preset[5])) {
            $diff = $this->preset[5] > 0 ? $mDate->diff($date) : $date->diff($mDate);
            $count = abs($this->preset[5]);
            while ($count-- > 0) [$date, $mDate] = [$date->add($diff), $mDate->add($diff)];
        }
        $dates = [$date, $mDate];
        sort($dates);

        [$this->from, $this->to] = $dates;
    }

    protected function chartsDataFinalArray(
        array $cur,
        array $charts,
    ): stdClass {
        $buttons['H'] = $this->presetMod('hour');
        $buttons['D'] = $this->presetMod('day');
        $buttons['W'] = $this->presetMod('week');
        $buttons['M'] = $this->presetMod('month');
        $buttons[] = null;
        $buttons['⟲'] = $this->httpQ();
        $buttons['-'] = $this->preset[1] > 1 ? $this->presetMod(1, false) : null;
        $buttons['+'] = $this->presetMod(1, true);
        $urls = (object)['buttons' => $buttons];

        if (empty($this->preset[5])) $this->preset[5] = 0;
        if (empty($this->preset[4])) {
            $urls->back = $this->httpQ([
                'from' => (clone $this->from)->add($this->to->diff($this->from))->format(self::DATE_FORMAT),
                'to' => (clone $this->to)->add($this->to->diff($this->from))->format(self::DATE_FORMAT),
            ]);
            $urls->fwd = $this->httpQ([
                'from' => (clone $this->from)->add($this->from->diff($this->to))->format(self::DATE_FORMAT),
                'to' => (clone $this->to)->add($this->from->diff($this->to))->format(self::DATE_FORMAT),
            ]);
        } else {
            $urls->back = $this->presetMod(5, false);
            $urls->fwd = $this->presetMod(5, true);
        }

        return (object)[
            'dev' => $this->dev,
            'urls' => $urls,
            'current' => (object)$cur,
            'charts' => (object)$charts,
            'from' => $this->from->format(self::DATE_FORMAT),
            'to' => $this->to->format(self::DATE_FORMAT),
        ];
    }
    private function httpQ(array $fields = []): string
    {
        return http_build_query(array_merge(['chart' => $this->dev->get('name')], $fields));
    }

    private function presetMod(mixed $position, ?bool $modify = null): string
    {
        $preset = $this->preset;
        if (is_bool($modify)) {
            if ($modify) $preset[$position]++;
            else $preset[$position]--;
        } elseif (is_string($position)) {
            if ($preset[4] == $position)
                $preset[3] = $preset[3] == 'clean-' ? '' : 'clean-';
            else $preset[4] = $position;
        }
        if ($preset[5] == 0) $preset[5] = '';
        elseif ($preset[5] > 0) $preset[5] = " $preset[5]";
        if ($preset[1] == 1) $preset[1] = 0;
        $preset[2] = empty($preset[1]) ? '' : '*';

        $result = ['preset' => implode(array_filter($preset))];
        if (empty($result['preset'])) unset($result['preset']);

        return $this->httpQ($result);
    }
}
