<?php

class Html
{
    public static function getClientsJson(): string
    {
        $configs = Config::all();
        if (empty($configs)) return json_encode([]);

        $maxKeys = max(array_map(fn ($i) => count(explode('_', $i)), array_keys($configs)));

        $children = [
            ['tag' => 'th', 'params' => ['colspan' => $maxKeys], 'text' => 'Device'],
            ['tag' => 'th', 'text' => 'Status'],
            ['tag' => 'th', 'text' => 'Last change'],
            ['tag' => 'th', 'text' => 'Last On'],
            ['tag' => 'th', 'text' => 'Last Off'],
        ];

        if (!empty(Auth::get('cliAdm')))
            $children[] = ['tag' => 'th', 'text' => 'Update'];

        return json_encode([['tag' => 'br'], ['tag' => 'table', 'children' => array_merge([
            ['tag' => 'tr', 'children' => $children]
        ], self::dataJsonRows($configs))]]);
    }

    protected static function dataJsonRows(array $configs): array
    {
        $split = array_map(
            fn ($i) => explode('_', $i),
            array_combine(array_keys($configs), array_keys($configs))
        );

        foreach ($configs as $dev => $cfg) {
            $status = $cfg->get('current');
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
                ['tag' => 'td', 'params' => [
                    'style' => ['color' => $status ? 'green' : 'red']
                ], 'text' => $status ? 'On' : 'Off'],
                ['tag' => 'td', 'text' => Helper::after($cfg->get((int)$status))],
                ['tag' => 'td', 'text' => Helper::dateFormat($cfg->get(1))],
                ['tag' => 'td', 'text' => Helper::dateFormat($cfg->get(0))],
            ])];

            if (Auth::client($cfg->name(), true))
                $row['children'][] = ['tag' => 'td', 'children' => [[
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
