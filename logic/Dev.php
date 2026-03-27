<?php

use Parts\{Cfg, Point};
use Helpers\DB;
use Helpers\Helper;

class Dev extends Cfg
{
    protected const TABLE = 'points';

    public static function create(string $dev): self
    {
        $dev = DB::start()->one(
            'SELECT * FROM points_view WHERE name=:dev',
            [':dev' => $dev]
        );
        if (empty($dev['class'])) exit("Device not found!\n");

        return new self($dev);
    }

    public static function createPoint(string $dev, array $data = []): ?Point
    {
        $cfg = self::create($dev);

        return class_exists($cfg->class())
            ? $cfg->class()::init($cfg)->check($data)
            : null;
    }

    public static function all(): array
    {
        if (IS_WEB) {
            $where = 'JSON_EXTRACT(users, :id) IS NOT Null';
            $params[':id'] = '$."' . Auth::id() . '"';
        } else {
            $where = 'active=:active';
            $params[':active'] = 1;
        }

        $query = "SELECT * FROM points_view WHERE $where";
        $cfgs = array_map(fn($i) =>
        new self($i), DB::start()->all($query, $params) ?? []);

        return $cfgs ?? [];
    }

    public static function runAll($paralel = false, $mode = 'r')
    {
        foreach (self::all(true) as $item)
            if ($paralel) $stdouts[] =
                popen(Helper::phpExec('run', $item->get('name')), $mode);
            else self::createPoint($item->get('name'));

        foreach ($stdouts ?? [] as $stdout)
            while (!feof($stdout)) echo fgets($stdout);
    }

    public function class()
    {
        return "\\Points\\" . $this->get('class');
    }
}
