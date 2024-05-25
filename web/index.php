<?php
include '../bootstrap.php';
?>

<body>
    <br><br>
    <table>
        <tr>
            <th colspan=2>Device</th>
            <th>Status</th>
            <th>Last change</th>
            <th>Last On</th>
            <th>Last Off</th>
        </tr>
        <?php foreach (Config::all() as $devKey => $devCfg) : ?>
            <?php if (is_array($devCfg)) : ?>
                <tr height="0">
                    <td rowspan="<?= count($devCfg) + 1 ?>">
                        <?= strtoupper($devKey) ?>
                    </td>
                </tr>
                <?php foreach ($devCfg as $typeKey => $typeCfg) : ?>
                    <?= Helper::getRow($typeKey, $typeCfg) ?>
                <?php endforeach ?>
            <?php else : ?>
                <?= Helper::getRow($devKey, $devCfg, 2) ?>
            <?php endif ?>
        <?php endforeach ?>
    </table>
</body>

<script>
    function time() {
        setTimeout(time, 1000);
        let els = document.getElementsByClassName('after');
        Array.prototype.forEach.call(els, function(el) {
            let time = el.innerText.split('d '),
                d = parseInt(time[0]);
            time = time[1].split(':');
            let h = parseInt(time[0]),
                m = parseInt(time[1]),
                s = parseInt(time[2]);

            if (++s >= 60) {
                s -= 60;
                if (++m >= 60) {
                    m -= 60;
                    if (++h >= 24) {
                        h -= 24;
                        ++d;
                    }
                }
            }

            el.innerText = d + 'd ' + [
                ('00' + h).slice(-2),
                ('00' + m).slice(-2),
                ('00' + s).slice(-2)
            ].join(':');
        });
    }
    setTimeout(time, 1000);
</script>

<style>
    body {
        background-color: #161616;
        height: 100%;
        margin: 0;
        padding: 0;
        color: lightgray;
        top: 0;
    }

    table {
        width: 80%;
        margin: 0 10%;
    }

    table,
    th,
    td {
        border: 1px solid #646464;
        border-collapse: collapse;
    }

    th {
        font-weight: bolder;
    }

    th,
    td {
        padding: 1em;
        text-align: center;
    }
</style>
