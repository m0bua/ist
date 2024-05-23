<?php
class Helper
{
    public static function after(string $date, bool $withPref = true): string
    {
        $result = '';
        if (!empty($date)) {
            $result = date_create($date)
                ->diff(date_create())
                ->format('%r%a:%H:%I:%S');
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
}
