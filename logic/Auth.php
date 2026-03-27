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

    function __construct()
    {
        ini_set('session.cache_expire', self::SESSION_EXP_MIN);
        ini_set('session.gc_maxlifetime', self::SESSION_EXP_SEC);
        session_start(['cookie_lifetime' => self::SESSION_EXP_SEC]);
    }

    public function start(): void
    {
        $this->autorize();

        if (
            $_SERVER['SCRIPT_NAME'] !== self::LOGIN_PATH
            && !$this->authorized($_GET['d'] ?? self::getUser() ?? null)
        ) Helper::redirect(self::LOGIN_PATH);
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
        if (!empty($_SESSION['id'])) {
            $where[] = 'auth.id=:id';
            $params[':id'] = $_SESSION['id'];
        } elseif (!empty(self::getUser())) {
            $where[] = 'auth.login=:login';
            $params[':login'] = self::getUser();
        } else return [];

        if ($admin) {
            $where[] = 'user_points.admin=:admin';
            $params[':admin'] = 1;
        }
        if (!empty($where)) $sql .= ' WHERE ' . implode(' AND ', $where);

        $result = array_map(
            fn($i) => $i['name'],
            DB::start()->all($sql, $params ?? []) ?? []
        );

        return $result;
    }

    private function autorize(): void
    {
        $user = self::getUser();
        $pass = self::getPwd();
        if (empty($user) || empty($pass)) return;
        $model = DB::start()->one(
            'SELECT * FROM auth WHERE login=:user',
            [':user' => $user]
        );
        $created = Helper::date();
        if (empty($model['hash'])) $model = [
            'login' => $user,
            'auth' => false,
            'hash' => $this->hash($user, $pass, $created),
            'created' => $created,
            'create_ip' => Helper::ip()
        ];

        $this->setData($model);
        $hash = $this->hash($user, $pass, $this->data['created']);

        if ($this->data['hash'] === $hash) {
            if ((bool)$this->data['auth'])
                $_SESSION['id'] = $this->data['id'] ?? null;
            $this->data['last_login'] = Helper::date();
            $this->data['last_login_ip'] = Helper::ip();
        }
        if (isset($_SESSION['id']) && empty($_GET)) Helper::redirect();
    }

    private function hash(string $usr, string $pwd, string $created): string
    {
        return sha1(implode('-', [$usr, $pwd, Helper::date($created)]));
    }

    private function authorized(?string $client = null): bool
    {
        if (empty($client)) {
            if (empty($_SESSION['id'])) return false;
            $sql = "SELECT * FROM auth WHERE id=:id AND auth=1";
            $user = DB::start()->one($sql, [':id' => $_SESSION['id']]);
            if (empty($user)) {
                unset($_SESSION['id']);
                return false;
            }
        }

        return empty($client) ? true : in_array($client, self::clients(false));
    }

    private static function getUser()
    {
        return self::getCrd()['username'] ?? self::getCrd()['u'] ?? '';
    }

    private static function getPwd()
    {
        return self::getCrd()['password'] ?? self::getCrd()['p'] ?? '';
    }

    private static function getCrd()
    {
        return empty($_POST) ? $_GET : $_POST;
    }
}
