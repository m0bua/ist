<?php
class Message
{
    private Config $cfg;

    public function __construct()
    {
        $this->cfg = new Config('tg');
    }

    public function send(string $text, ?string $chatId = null): void
    {
        if (empty($text)) return;

        echo $text . "\n";

        if (
            empty($chatId)
            || empty($this->cfg->get('id'))
            || empty($this->cfg->get('key'))
        ) return;

        $this->tg($text, $chatId);
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
}
