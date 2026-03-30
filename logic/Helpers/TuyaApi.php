<?php

namespace Helpers;

use Dev;
use tuyapiphp\TuyaApi as Api;

class TuyaApi extends Api
{
    const DELAY = '150 SECOND';

    public static function get(Dev $cfg): ?object
    {
        $date = 'CURRENT_TIMESTAMP - INTERVAL ' . $cfg->get('wait', self::DELAY);
        $sql = 'SELECT * FROM tuya AS t LEFT JOIN tuya_log AS l ON l.t_id = t.id'
            . " AND l.date>=($date) WHERE t.id=:id ORDER BY l.date DESC LIMIT 1";
        $item = DB::start()->one($sql, [':id' => $cfg->get('address')]);

        if (empty($item['data'])) {
            $tuya = new Api($item);
            $token = $tuya->token->get_new()->result->access_token ?? null;
            $dev = $tuya->devices($token)->get_details($item['device']);
            if (empty($dev->result)) {
                echo ($dev->msg ?? json_encode($dev)) . "\n";
                return null;
            }
            $res = $dev->result;

            $res->status = (object)Helper::pluck($res->status, 'code', 'value');
            foreach ($cfg->get('params.tData.decode', []) as $key => $field)
                foreach (self::decode($res->status->{$key}, $field['fields']) as $k => $i)
                    $res->status->{"$k{$field['name']}"} = $i;
            $res = json_encode($res);

            DB::start()->upsert('tuya_log', ['t_id' => $cfg->get('address'), 'data' => $res]);
        } else $res = $item['data'];

        $res = json_decode($res);

        return $res;
    }

    private static function decode(string $base64Data, array $fields): array
    {
        $i = 0;
        $hex = bin2hex(base64_decode($base64Data));
        foreach ($fields as $key => $offset) {
            $result[$key] = hexdec(substr($hex, $i, $offset));
            $i += $offset;
        }

        return $result ?? [];
    }
}
