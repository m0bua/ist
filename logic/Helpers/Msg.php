<?php

namespace Helpers;

use Dev;

class Msg
{
    private const DEFAULT_MESSAGES = [
        '' => '{status::🔴||🟢||📉||📈} {dev} status is '
            . '{status::off||on||low||high}{v:: (#V)}{after:: after #}.',
        'Header' => '{dev}: ',
        'Ip' => '{ip::IP changed #&to #& => #}.',
        'Text' => '{message}.',
    ];

    private Dev $cfg;

    public function send(Dev $cfg): void
    {
        $this->cfg = $cfg;

        if ($this->cfg->changed('status')) $text[] = self::prepare();

        if ($this->cfg->changed('address') && $this->cfg->get('params.showIp', false)) {
            if (empty($text)) $text[] = self::prepare('header');
            $text[] = self::prepare('ip');
        }

        if ($this->cfg->changed('message')) {
            if (empty($text)) $text[] = self::prepare('header');
            $text[] = self::prepare('text');
        }

        $text = array_filter($text ?? []);
        if (empty($text)) {
            echo "Nothing changed.\n";
            return;
        }

        $text = implode("\n", $text);
        echo $text . "\n";

        $this->tg($text, $cfg);
    }

    private function tg(string $text, Dev $cfg): void
    {
        if (
            empty($cfg->get('tg.id')) ||
            empty($cfg->get('tg.key')) ||
            empty($cfg->get('tg.chat'))
        ) return;

        file_get_contents('https://api.telegram.org/'
            . strtr('bot{id}:{key}/sendMessage?', [
                '{id}' => $cfg->get('tg.id', false),
                '{key}' => $cfg->get('tg.key', false),
            ]) . http_build_query([
                'chat_id' => $cfg->get('tg.chat', false),
                'text' => $text,
            ]));
    }

    private function prepare(string $pattern = ''): ?string
    {
        $pattern = ucfirst($pattern);
        $msg = $this->cfg->get("params.msg{$pattern}Pattern") ?? self::DEFAULT_MESSAGES[$pattern] ?? '';
        $params = [
            'dev' => $this->cfg->name(),
            'status' => $this->cfg->get('status'),
            'after' => Helper::after(
                $this->cfg->get('dates.' . (int)!$this->cfg->get('status'), ''),
                $this->cfg->get('params.dateDiffFormat')
            ),
            'address' => '',
        ];
        foreach (array_keys($this->cfg->get()) as $key)
            $params[$key] = $this->cfg->changed($key) ? $this->cfg->get($key) : '';
        if ($this->cfg->changed('address'))
            $params['address'] = empty($this->cfg->getOrig('params.ip'))
                ? $this->cfg->get('params.ip')
                : [$this->cfg->getOrig('params.ip'), $this->cfg->get('params.ip')];

        self::fields($msg, $params);

        return $msg;
    }

    private static function fields(?string &$msg, array $params)
    {
        $preg = '/{[^{}]+}/';
        preg_match_all($preg, $msg, $matches);
        foreach (reset($matches) as $item) {
            $trim = trim($item, '{}');
            if (strpos($trim, '::') !== false) {
                [$key, $field] = explode('::', $trim);
                $param = $params[$key];
                if (strpos($field, '#&') !== false) {
                    [$field, $single, $add] = explode('&', $field);
                    if (is_array($param)) {
                        $field = str_replace('#', reset($param), $field);
                        if (count($param) > 1)
                            foreach (array_slice($param, 1) as $v)
                                $field .= str_replace('#', $v, $add);
                    } elseif (!empty($param)) {
                        $single = str_replace('#', $param, $single);
                        $field = str_replace('#', $single, $field);
                    } else $field = '';
                } elseif (strpos($field, '#') !== false)
                    $field = empty($param) ? ''
                        : str_replace('#', $param, $field);
                elseif (strpos($field, '||') !== false) {
                    $ex = explode('||', $field);
                    if (!isset($ex[$param])) $msg = '';
                    $field = $ex[$param] ?? '';
                }
            } else $field = $params[$trim] ?? '';

            if (!empty($msg)) $msg = str_replace($item, $field, $msg);
        }

        if (preg_match($preg, $msg)) self::fields($msg, $params);
    }
}
