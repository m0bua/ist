<?php

namespace Helpers;

use tuyapiphp\TuyaApi as Api;

class TuyaApi extends Api
{
    const DELAY = '5 MINUTE';

    public static function get(string $id, ?string $delay = null): ?object
    {
        $date = 'CURRENT_TIMESTAMP - INTERVAL ' . ($delay ?? self::DELAY);
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
            $res = json_encode($res);

            DB::start()->upsert('tuya_log', ['t_id' => $id, 'data' => $res]);
        } else $res = $item['data'];

        $res = json_decode($res);

        return $res;
    }
}
