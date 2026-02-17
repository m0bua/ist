<?php

use Parts\Storage;
use Helpers\{DB, Helper};

class Auth
{
    use Storage;

    const SESSION_EXP_MIN = 30 * 24 * 60;
    const SESSION_EXP_SEC = self::SESSION_EXP_MIN * 60;

    private const TABLE = 'auth';
    private const LOGIN_PATH = '/login.php';
    private static string $user;

    function __construct()
    {
        ini_set('session.cache_expire', self::SESSION_EXP_MIN);
        ini_set('session.gc_maxlifetime', self::SESSION_EXP_SEC);
        session_start(['cookie_lifetime' => self::SESSION_EXP_SEC]);
    }

    public function start(): void
    {
        $crd = empty($_POST) ? $_GET : $_POST;
        $this->autorize(
            $crd['username'] ?? $crd['u'] ?? '',
            $crd['password'] ?? $crd['p'] ?? '',
        );

        if (
            $_SERVER['SCRIPT_NAME'] !== self::LOGIN_PATH
            && !$this->authorized($_GET['d'] ?? self::$user ?? null)
        ) exit(header('location: ' . self::LOGIN_PATH));
    }

    public static function id(): ?string
    {
        return $_SESSION['id'] ?? null;
    }

    public static function client(string $name, bool $admin): bool
    {
        $clients = self::clients($admin);

        return is_array($clients) ? in_array($name, $clients) : false;
    }

    public static function clients(bool $admin = true): array
    {
        $sql = "SELECT points.name FROM user_points
            INNER JOIN auth ON auth.id = user_points.user_id
            INNER JOIN points ON points.id = user_points.point_id";
        if (!empty($_SESSION['id'])) $where[] = "auth.id='{$_SESSION['id']}'";
        elseif (isset(self::$user)) $where[] = "auth.login='" . self::$user . "'";
        else return [];
        if ($admin) $where[] = "user_points.admin=1";
        if (!empty($where)) $sql .= ' WHERE ' . implode(' AND ', $where);

        $result = array_map(fn($i) => $i['name'], DB::start()->all($sql) ?? []);

        return $result;
    }

    private function autorize(string $user, string $pass): void
    {
        if (empty($user) || empty($pass)) return;
        self::$user = $user;
        $model = DB::start()->one("SELECT * FROM auth WHERE login='$user'");
        if (empty($model['hash'])) {
            $created = Helper::date();
            $model = [
                'login' => $user,
                'auth' => false,
                'hash' => $this->hash($user, $pass, $created),
                'created' => $created,
                'create_ip' => Helper::ip()
            ];
        }

        $this->setData($model);
        $hash = $this->hash($user, $pass, $this->data['created']);

        if ($this->data['hash'] === $hash) {
            if ((bool)$this->data['auth'])
                $_SESSION['id'] = $this->data['id'] ?? null;
            $this->data['last_login'] = Helper::date();
            $this->data['last_login_ip'] = Helper::ip();
        }
        if (isset($_SESSION['id']) && empty($_GET))
            exit(header('location: /'));
    }

    private function hash(string $usr, string $pwd, string $created): string
    {
        return sha1(implode('-', [$usr, $pwd, Helper::date($created)]));
    }

    private function authorized(?string $client = null): bool
    {
        if (empty($client)) {
            if (empty($_SESSION['id'])) return false;
            $sql = "SELECT * FROM auth WHERE id='{$_SESSION['id']}' AND auth=1";
            $user = DB::start()->one($sql);
            if (empty($user)) {
                unset($_SESSION['id']);
                return false;
            }
        }

        return empty($client) ? true : in_array($client, self::clients(false));
    }
}
