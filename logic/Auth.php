<?php

class Auth
{
    private Config $cfg;
    private array $session = [];

    function __construct()
    {
        session_start();
        $this->session = $_SESSION;
        $this->cfg = new Config('auth');
    }

    function __destruct()
    {
        $_SESSION = $this->session;
    }

    public function login(): bool
    {
        $this->autorize([
            $_POST['username'] ?? null,
            $_POST['password'] ?? null,
        ]);

        return $this->authorized();
    }

    public function cli(): bool
    {
        $this->autorize([
            $_GET['u'] ?? null,
            $_GET['p'] ?? null,
        ]);

        return $this->authorized($_GET['d'] ?? null);
    }

    public function authorized(?string $client = null): bool
    {
        $user = ($this->session['user'] ?? null);

        $result = ($this->session['auth'] ?? false) === true &&
            $this->cfg->get("$user.auth", false);

        if (empty($client)) {
            $result = $result &&
                !$this->cfg->get("$user.client", false);
        } else {
            $result = $result &&
                $this->cfg->get("$user.client", false) === $client;
        }

        return $result;
    }

    private function autorize(array $params): void
    {
        [$u, $p] = $params;
        if (empty($u)) return;

        $user = $this->cfg->get($u, [
            'auth' => false,
            'hash' => $this->hash($u, $p),
            'created' => date_create()->format('c'),
            'create_ip' => $_SERVER['SERVER_ADDR'] ?? null
        ]);
        $this->session['auth'] = $this->hash($u, $p) === $user['hash'] && $user['auth'] === true;
        if ($this->session['auth']) {
            $this->session['user'] = $u;
            $user['last_login'] = date_create()->format('c');
            $user['last_login_ip'] = $_SERVER['SERVER_ADDR'] ?? null;
        }
        $user['last_ip'] = $_SERVER['SERVER_ADDR'] ?? null;
        $this->cfg->set($u, $user);
    }

    private function hash(string $usr, string $pwd): string
    {
        return sha1(implode('-', [
            $usr, $pwd,
            $this->cfg->get("$usr.created", date_create()->format('c')),
        ]));
    }
}
