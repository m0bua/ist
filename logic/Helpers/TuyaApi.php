<?php

namespace Helpers;

use tuyapiphp\TuyaApi as Api;
use Dev;

class TuyaApi extends Api
{
    const DELAY = '150 SECOND';

    public static function get(Dev $cfg): ?object
    {
        $id = $cfg->get('address');
        $date = 'CURRENT_TIMESTAMP - INTERVAL ' . $cfg->get('wait', self::DELAY);
        $sql = "SELECT * FROM tuya AS t LEFT JOIN tuya_log AS l ON l.t_id = t.id"
            . " AND l.date >= ($date) WHERE t.id=$id ORDER BY l.date DESC LIMIT 1";
        $item = DB::start()->one($sql);

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
                foreach (self::phaseParse($res->status->{$key}) as $k => $i)
                    $res->status->{"$k$field"} = $i;
            $res = json_encode($res);

            DB::start()->upsert('tuya_log', ['t_id' => $id, 'data' => $res]);
        } else $res = $item['data'];

        $res = json_decode($res);

        return $res;
    }

    private static function phaseParse($base64Data)
    {
        $hex = bin2hex(base64_decode($base64Data));

        return [
            'V' => hexdec(substr($hex, 0, 4)),
            'A' => hexdec(substr($hex, 4, 6)),
            'W' => hexdec(substr($hex, 10, 6)),
        ];
    }
}
