<?php
class Msg
{
    protected const DEFAULT_MESSAGES = [
        '' => '{status::🔴||🟢} {dev} status is '
            . '{status::off||on}{after:: after #}.',
        'Header' => '{dev}: ',
        'Ip' => '{ip::IP changed #&to #& => #}.',
        'Text' => '{message}.',
    ];
    protected Dev $cfg;

    function __construct()
    {
        $this->cfg = new Dev('tg');
    }

    public function send(Dev $cfg): void
    {
        if ($cfg->changed('current')) {
            $text[] = self::prepare($cfg);
        }

        if ($cfg->changed('ip') && $cfg->get('showIp', false)) {
            if (empty($text)) $text[] = self::prepare($cfg, 'header');
            $text[] = self::prepare($cfg, 'ip');
        }

        if ($cfg->changed('message')) {
            if (empty($text)) $text[] = self::prepare($cfg, 'header');
            $text[] = self::prepare($cfg, 'text');
        }

        $text = array_filter($text ?? []);
        if (empty($text)) {
            echo "Nothing changed.\n";
            return;
        }

        $text = implode("\n", $text);
        echo $text . "\n";

        if (
            $cfg->get('active', false)
            && $cfg->get('tgChat', false)
            && $cfg->get('tgId', $this->cfg->get('id', false))
            && $cfg->get('tgKey', $this->cfg->get('key', false))
        ) $this->tg($text, $cfg);
    }

    protected function tg(string $text, Dev $cfg): void
    {
        file_get_contents('https://api.telegram.org/'
            . strtr('bot{id}:{key}/sendMessage?', [
                '{id}' => $cfg->get('tgId', $this->cfg->get('id', false)),
                '{key}' => $cfg->get('tgKey', $this->cfg->get('key', false)),
            ]) . http_build_query([
                'chat_id' => $cfg->get('tgChat', false),
                'text' => $text,
            ]));
    }

    protected static function prepare(Dev $cfg, string $pattern = ''): string
    {
        $pattern = ucfirst($pattern);
        $msg = $cfg->get("msg${pattern}Pattern", self::DEFAULT_MESSAGES[$pattern] ?? '');
        $params = [
            'dev' => strtoupper($cfg->dev()),
            'status' => $cfg->get('current'),
            'after' => Helper::after(
                $cfg->get((int)!$cfg->get('current'), ''),
                $cfg->get('dateDiffFormat')
            ), 'ip' => '',
        ];
        foreach (array_keys($cfg->get()) as $key)
            $params[$key] = $cfg->changed($key) ? $cfg->get($key) : '';
        if ($cfg->changed('ip'))
            $params['ip'] = empty($cfg->getOrig('ip'))
                ? $cfg->get('ip') : [$cfg->getOrig('ip'), $cfg->get('ip')];

        self::fields($msg, $params);

        return $msg;
    }

    protected static function fields(string &$msg, array $params): void
    {
        $preg = '/{[^{}]+}/';
        preg_match_all($preg, $msg, $matches);
        foreach (reset($matches) as $item) {
            $trim = trim($item, '{}');
            var_dump([$item, $trim]);
            if (strpos($trim, '::') !== false) {
                [$key, $field] = explode('::', $trim);
                if (strpos($field, '#&') !== false) {
                    [$field, $single, $add] = explode('&', $field);
                    if (is_array($params[$key])) {
                        $field = str_replace('#', reset($params[$key]), $field);
                        if (count($params[$key]) > 1)
                            foreach (array_slice($params[$key], 1) as $v)
                                $field .= str_replace('#', $v, $add);
                    } elseif (!empty($params[$key])) {
                        $single = str_replace('#', $params[$key], $single);
                        $field = str_replace('#', $single, $field);
                    } else $field = '';
                } elseif (strpos($field, '#') !== false)
                    $field = empty($params[$key]) ? ''
                        : str_replace('#', $params[$key], $field);
                elseif (strpos($field, '||') !== false)
                    $field = explode('||', $field)[$params[$key]];
            } else $field = $params[$trim] ?? '';

            $msg = str_replace($item, $field, $msg);
        }

        if (preg_match($preg, $msg)) self::fields($msg, $params);
    }
}
