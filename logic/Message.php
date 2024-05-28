<?php
class Message
{
    private Config $cfg;

    public function __construct()
    {
        $this->cfg = new Config('tg');
    }

    public function send(Config $cfg): void
    {
        $text = self::prepare($cfg);

        if (empty($text)) return;

        echo $text . "\n";

        if (
            empty($chatId)
            || empty($this->cfg->get('id'))
            || empty($this->cfg->get('key'))
        ) return;

        $this->tg($text, $cfg->get('tgChat'));
    }

    private function tg(string $text, string $chatId): void
    {
        file_get_contents('https://api.telegram.org/'
            . strtr('bot{id}:{key}/sendMessage?', [
                '{id}' => $this->cfg->get('id'),
                '{key}' => $this->cfg->get('key'),
            ]) . http_build_query([
                'chat_id' => $chatId,
                'text' => $text,
            ]));
    }

    private static function prepare(Config $cfg): string
    {
        $msg = $cfg->get('msgPattern', '{status:🔴||🟢} {dev} status is '
            . '{status:off||on}{after: after #}{ip:, IP changed #&to #& => #}.');
        $params = [
            'dev' => strtoupper($cfg->name()), 'status' => $cfg->get('current'), 'ip' => '',
            'after' => Helper::after($cfg->get((int)!$cfg->get('current'), '')),
        ];
        if ($cfg->changed('ip')) {
            $params['ip'] = empty($cfg->getOrig('ip'))
                ? $cfg->get('ip') : [$cfg->getOrig('ip'), $cfg->get('ip')];
        }
        preg_match_all('/{[^}]+}/', $msg, $matches);
        foreach (reset($matches) as $item) {
            $trim = trim($item, '{}');
            if (strpos($trim, ':')) {
                [$key, $field] = explode(':', $trim);
                if (strpos($field, '#&')) {
                    [$field, $single, $add] = explode('&', $field);
                    if (is_array($params[$key])) {
                        $field = str_replace('#', reset($params[$key]), $field);
                        if (count($params[$key]) > 1)
                            foreach (array_slice($params[$key], 1) as $v)
                                $field .= str_replace('#', $v, $add);
                    } elseif (!empty($params[$key])) {
                        $single = str_replace('#', $params[$key], $single);
                        $field = str_replace('#', $single, $field);
                    } else {
                        $field = '';
                    }
                } elseif (strpos($field, '#')) $field = empty($params[$key]) ? ''
                    : str_replace('#', $params[$key], $field);
                elseif (strpos($field, '||'))
                    $field = explode('||', $field)[$params[$key]];
            } else $field = $params[$trim] ?? '';
            $msg = str_replace($item, $field, $msg);
        }

        return $msg;
    }
}
