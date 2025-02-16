<?php

use Parts\{Cfg, Point};
use Helpers\DB;
use Helpers\Helper;

class Dev extends Cfg
{
    protected const TABLE = 'points';
    public const CFG_TYPES = [
        Request::class,
        Income::class,
    ];

    public static function create(string $dev): self
    {
        $dev = DB::start()->one("SELECT * FROM points_view WHERE name='$dev'");

        if (empty($dev['class']))
            exit("Device not found!\n");
        if (!in_array($dev['class'], self::CFG_TYPES))
            exit("Uncnown type!\n");

        return new self($dev);
    }

    public static function createPoint(string $dev): Point
    {
        $cfg = self::create($dev);

        return $cfg->get('class')::init($cfg);
    }

    public static function all(): array
    {
        $query = 'SELECT * FROM points_view WHERE ' .
            (IS_WEB ? "JSON_EXTRACT(users, '$.\""
                . Auth::id() . "\"') IS NOT Null"
                : 'active=1');

        foreach (DB::start()->all($query) ?? [] as $item)
            $cfgs[] = new self($item);

        return $cfgs ?? [];
    }

    public static function runAll($paralel = false, $mode = 'r')
    {
        foreach (self::all(true) as $item)
            if ($paralel) $stdouts[] =
                popen(Helper::phpExec('run', $item->get('name')), $mode);
            else Dev::createPoint($item->get('name'))->check();

        foreach ($stdouts ?? [] as $stdout)
            while (!feof($stdout)) echo fgets($stdout);
    }
}
