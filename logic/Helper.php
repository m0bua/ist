<?php
class Helper
{
    protected const DEFAULT_WAIT = '180';
    protected static ?DateTimeZone $tz = null;

    public static function after(?string $date, bool $withPref = false): string
    {
        $result = '';
        if (!empty($date)) {
            $result = date_create($date)
                ->diff(date_create())
                ->format('%r%ad %H:%I:%S');
            if ($withPref) $result = ' after ' . $result;
        }

        return $result;
    }

    public static function changed(Config $cfg): bool
    {
        $date = $cfg->get((int)$cfg->get('current'));
        if (empty($date)) return true;

        $wait = empty($_GET['t'])
            ? $cfg->get('wait', self::DEFAULT_WAIT)
            : $_GET['t'] + 1;

        return date_create("-$wait sec") > date_create($date);
    }

    public static function dateFormat(?string $date): string
    {
        if (empty($date)) return '';

        $date = date_create($date);
        if (!empty(self::tz())) $date->setTimezone(self::tz());

        return $date->format('Y.m.d H:i');
    }

    public static function tz(): ?DateTimeZone
    {
        $tzList = timezone_identifiers_list();
        if (isset($_GET['tz']) && in_array($_GET['tz'], $tzList))
            self::$tz = new DateTimeZone($_GET['tz']);

        return self::$tz;
    }

    public static function getArrayKey(
        array $array,
        ?string $field = null,
        mixed $default = null
    ): mixed {
        if (is_null($field)) return $array;
        if (isset($array[$field])) return $array[$field];
        foreach (explode('.', $field) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        return $array;
    }

    public static function popenAll($paralel = false, $mode = 'r')
    {
        foreach (Dev::CFG_TYPES as $type) {
            $cmd = self::path("$type.php");
            $class = ucfirst($type);
            foreach (array_keys(Dev::all($type, true)) as $dev) {
                if ($paralel) $stdouts[] = popen("php $cmd $dev", $mode);
                else (new $class([$type, $dev]))->check();
            }
        }

        foreach ($stdouts ?? [] as $stdout)
            while (!feof($stdout)) echo fgets($stdout);
    }

    public static function path(mixed $path): string
    {
        if (is_string($path)) $path = [$path];

        return implode(DIRECTORY_SEPARATOR, array_merge([ROOT], $path));
    }
}
