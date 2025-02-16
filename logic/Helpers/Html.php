<?php

namespace Helpers;

use Auth, Dev;

class Html
{
    public static function getClientsJson(): string
    {
        foreach (Dev::all() as $item)
            $configs[$item->dev()] = $item;

        if (empty($configs))
            return json_encode([]);

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

    protected static function dataJsonRows(array $configs): array
    {
        $split = array_map(
            fn($i) => explode('_', $i),
            array_combine(array_keys($configs), array_keys($configs))
        );
        $max = max(array_map(fn($i) => count($i), $split));

        foreach ($configs as $dev => $cfg) {
            $status = (bool)$cfg->get('status');
            $children = [];
            foreach ($split[$dev] as $k => $name) {
                $same = array_filter($split, function ($item) use ($split, $dev, $k) {
                    for ($i = 0; $i <= $k; $i++) $res = ($res ?? true)
                        && (($item[$i] ?? '') === $split[$dev][$i]);
                    return $res ?? false;
                });
                $blockMax = max(array_map(fn($i) => count($i), $same));

                if (array_search($dev, array_keys($same)) === 0)
                    $children[] = ['tag' => 'td', 'params' => [
                        'rowspan' => count($same),
                        'colspan' => count($split[$dev]) === $blockMax
                            && $k === array_key_last($split[$dev])
                            ? $max + 1 - count($split[$dev]) : 1,
                    ], 'text' => strtoupper($name)];
            }

            if (count($split[$dev]) < $blockMax)
                $children[] = ['tag' => 'td', 'params' => [
                    'rowspan' => 1,
                    'colspan' => $max - count($split[$dev]),
                ], 'text' => null];

            $row = ['tag' => 'tr', 'children' => array_merge($children, [
                ['tag' => 'td', 'params' => [
                    'style' => ['color' => $status ? 'green' : 'red']
                ], 'text' => $status ? 'On' : 'Off'],
                ['tag' => 'td', 'text' => Helper::after(
                    $cfg->get('dates.' . (int)$status),
                    $cfg->get('params.dateDiffFormat')
                )],
                ['tag' => 'td', 'text' => Helper::dateFormat($cfg->get('dates.1'), $cfg->get('params.dateFormat'))],
                ['tag' => 'td', 'text' => Helper::dateFormat($cfg->get('dates.0'), $cfg->get('params.dateFormat'))],
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
