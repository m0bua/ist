<?php

function load(?array &$data, ?string $file = null): void
{
    $file = file_name($file);
    $data = is_file($file) ? json_decode(file_get_contents($file), true) : [];
}

function save(array $data, ?string $file): bool
{
    return file_put_contents(file_name($file), json_encode($data) . "\n");
}

function file_name(?string $file): string
{
    return __DIR__ . '/' . ($file ?? 'statuses') . '.json';
}

function tg($text, ?string $chatId = null): void
{
    if (empty($text)) return;
    if (is_array($text)) $text = implode("\n", $text);

    load($cfg, 'tg');
    if (
        empty($chatId)
        || empty($cfg['id'])
        || empty($cfg['key'])
    ) echo $text . "\n";
    else {
        file_get_contents('https://api.telegram.org/'
            . strtr('bot{id}:{key}/sendMessage?', [
                '{id}' => $cfg['id'],
                '{key}' => $cfg['key'],
            ]) . http_build_query([
                'chat_id' => $chatId,
                'text' => $text,
            ]));
    }
}

function after(string $date, bool $withPref = true): string
{
    if (empty($date)) {
        return '';
    } else {
        $result = date_create($date)
            ->diff(date_create())
            ->format('%r%a:%H:%I:%S');
        if ($withPref) $result = ' after ' . $result;
        return $result;
    }
}

function direct(string $server, int $tries = 3): bool
{
    $port = 443;
    if (strpos($server, ':')) [$server, $port] = explode(':', $server);

    $status = false;
    for ($i = 1; $i <= $tries; $i++) connect($status, $server, $port);

    return $status;
}

function connect(bool &$status, string $server, int $port = 443, int $wait = 3): void
{
    if (!$status) {
        set_error_handler(function () use ($server, $port, $wait) {
            echo "No connection to $server:$port, waiting $wait sec.\n";
        });
        $result = !!fSockOpen($server, $port, $_, $_, $wait);
        restore_error_handler();
    } else $result = true;

    if ($status !== $result) echo "Connected to $server:$port!\n";

    $status = $result;
}

function msgPrepare(string $pattern, array $params): string
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
