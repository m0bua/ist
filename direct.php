<?php

require_once __DIR__ . '/functions.php';

if (count($argv) < 2 || count($argv) > 5)
    die('Usage: php direct.php test google.com {telegram_chat_id}'
        . "'{status:\ud83d\udd34|\ud83d\udfe2} "
        . 'Test {status:offline|online}'
        . "{after: after #}!'\n");
$dev = $argv[1];

load($data, "direct_$dev");

if (isset($argv[2]))
    $data['server'] = $argv[2];
if (empty($data['server']))
    die("No server selected!\n");
if (isset($argv[3]) && is_numeric($argv[3]))
    $data['tgChat'] = $argv[3];
if (isset($argv[4]))
    $data['msgPattern'] = $argv[4];

$status = direct($data['server'], $data['tries'] ?? 5);
if (($status || empty($data[!$status])
        || date_create($data[!$status] ?? '')
        < date_create($data['wait'] ?? '-180 sec'))
    && (($data['current'] ?? '') != $status)
) {
    $data['current'] = $status;
    $data[$status] = date_create()->format('c');
    if (isset($data['msgPattern'])) {
        $msg = msgPrepare($data['msgPattern'], [
            'dev' => $dev, 'status' => $status,
            'after' => after($data[!$status] ?? '', false),
        ]);
    } else {
        $msg = $dev . ' status is ' . $status ? 'on' : 'off';
        if (isset($data[!$status])) $msg .= after($data[!$status]);
        $msg .= '.';
    }
}

save($data, "direct_$dev");

tg($msg ?? null, $data['tgChat'] ?? null);

exit($status);
