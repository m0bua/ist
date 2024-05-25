<?php
class Helper
{
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
        return empty($date) ? '' : date_create($date)->format('Y.m.d H:i');
    }

    public static function getRow(string $dev, Config $cfg, int $span = 1): string
    {
        $status = $cfg->get('current');

        return strtr(file_get_contents(ROOT . '/views/row.txt'), [
            '{span}' => $span,
            '{name}' => $dev,
            '{color}' => $status ? 'green' : 'red',
            '{status}' => $status ? 'On' : 'Off',
            '{after}' => Helper::after($cfg->get((int)!$status)),
            '{1}' => Helper::dateFormat($cfg->get(1)),
            '{0}' => Helper::dateFormat($cfg->get(0)),
        ]);
    }
}
