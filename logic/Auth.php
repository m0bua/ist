<?php

use Parts\Storage;
use Helpers\{DB, Helper};

class Auth
{
    use Storage;

    private const TABLE = 'auth';
    private const LOGIN_path = '/login.php';

    function __construct()
    {
        session_start(['cookie_lifetime' => 30 * 24 * 60 * 60]);
    }

    public function start(): void
    {
        $crd = empty($_POST) ? $_GET : $_POST;
        $this->autorize(
            $crd['username'] ?? $crd['u'] ?? '',
            $crd['password'] ?? $crd['p'] ?? '',
        );

        if (
            $_SERVER['SCRIPT_NAME'] !== self::LOGIN_path
            && !$this->authorized($_GET['d'] ?? null)
        ) exit(header('location: ' . self::LOGIN_path));
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
            INNER JOIN points ON points.id = user_points.point_id
            WHERE auth.id='{$_SESSION['id']}'";
        if ($admin) $sql .= " AND user_points.admin=1";

        $result = array_map(fn($i) => $i['name'], DB::start()->all($sql));

        return $result;
    }

    private function autorize(string $user, string $pass): void
    {
        if (empty($user) || empty($pass)) return;
        $model = DB::start()->one("SELECT * FROM auth WHERE login='$user'");
        if (!$model) {
            $created = Helper::date();
            $model = [
                'login' => $user,
                'auth' => false,
                'hash' => $this->hash($user, $pass, $created),
                'created' => $created,
                'create_ip' => $_SERVER['SERVER_ADDR'] ?? null
            ];
        }

        $this->setData($model);
        $hash = $this->hash($user, $pass, $this->data['created']);

        if ($this->data['hash'] === $hash && (bool)$this->data['auth']) {
            $_SESSION['id'] = $this->data['id'] ?? null;
            $this->data['last_login'] = Helper::date();
            $this->data['last_login_ip'] = $_SERVER['SERVER_ADDR'] ?? null;
        }
        $this->data['last_ip'] = $_SERVER['SERVER_ADDR'] ?? null;
        if (isset($_SESSION['id']) && empty($_GET)) {
            header('location: /');
            exit();
        }
    }

    private function hash(string $usr, string $pwd, string $created): string
    {
        return sha1(implode('-', [$usr, $pwd, Helper::date($created)]));
    }

    private function authorized(?string $client = null): bool
    {
        if (empty($_SESSION['id'])) return false;
        $user = DB::start()->one("SELECT * FROM auth
            WHERE id='{$_SESSION['id']}' AND auth=1");
        if (empty($user)) {
            unset($_SESSION['id']);
            return false;
        }

        return empty($client) ? true : in_array($client, self::clients(false));
    }
}
