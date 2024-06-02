<?php

class Auth
{
    const LOGIN_FILE = '/login.php';

    private Config $cfg;
    private array $session = [];

    function __construct()
    {
        if (php_sapi_name() == 'cli') return;
        session_start(['cookie_lifetime' => 30 * 24 * 60 * 60]);
        $this->session = $_SESSION;
        $this->cfg = new Config('auth');
    }

    function __destruct()
    {
        $_SESSION = $this->session;
    }

    public function start(): void
    {
        if (php_sapi_name() == 'cli') return;
        $this->autorize(empty($_POST)
            ? [$_GET['u'] ?? null, $_GET['p'] ?? null]
            : [$_POST['username'] ?? null, $_POST['password'] ?? null]);

        if (
            $_SERVER['SCRIPT_NAME'] !== self::LOGIN_FILE
            && !$this->authorized($_GET['d'] ?? null)
        ) exit(header('location: ' . self::LOGIN_FILE));
    }

    public function authorized(?string $client = null): bool
    {
        $user = ($this->session['user'] ?? null);
        $result = ($this->session['auth'] ?? false) === true
            && $this->cfg->get("$user.auth", false)
            && (empty($client)
                ? !$this->cfg->get("$user.client", false)
                : in_array($client, $this->cfg
                    ->get("$user.client", false)));

        return $result;
    }

    public static function client(string $name, bool $admin): bool
    {
        if (php_sapi_name() == 'cli') return true;
        $clients = self::get($admin ? 'cliAdm' : 'clients', []);

        if ($clients === '*') return true;
        elseif (is_array($clients)) return in_array($name, $clients);
        else return false;
    }

    public static function get(?string $field = null, mixed $default = null): mixed
    {
        return Helper::getArrayKey($_SESSION, $field, $default);
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
            $this->session['clients'] = $this->cfg->get("$u.clients", []);
            $this->session['cliAdm'] = $this->cfg->get("$u.cliAdm", []);
            $user['last_login'] = date_create()->format('c');
            $user['last_login_ip'] = $_SERVER['SERVER_ADDR'] ?? null;
        }
        $user['last_ip'] = $_SERVER['SERVER_ADDR'] ?? null;
        $this->cfg->set($u, $user);
        if ($this->session['auth']) exit(header('location: /'));
    }

    private function hash(string $usr, string $pwd): string
    {
        return sha1(implode('-', [
            $usr, $pwd,
            $this->cfg->get("$usr.created", date_create()->format('c')),
        ]));
    }
}
