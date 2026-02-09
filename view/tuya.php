<?php $data = \Helpers\Html::getVoltageData($_GET) ?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= str_replace('_', ' ', $data->dev->get('params.name')) ?> Chart</title>
    <link rel=stylesheet type="text/css" href="res/tuya.css?v1">
    <script src="res/chart.min.js"></script>
</head>

<body id="body" class="dark">

    <section id="current">
        <a id="back" href="/">
            <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M15.6569 10H10M10 10V15.6569M10 10L18.364 18.364M20.8278 13.7568C21.3917 10.9096 20.5704 7.84251 18.364 5.63604C14.8492 2.12132 9.15076 2.12132 5.63604 5.63604C2.12132 9.15076 2.12132 14.8492 5.63604 18.364C7.84251 20.5704 10.9096 21.3917 13.7568 20.8278" stroke="#777" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            Back
        </a>
        <div id="status" style="background-color:dark<?= $data->dev->class()::COLORS[$data->dev->get('status')] ?>">
            <h1>
                <?php if ($data->current->online === 'true'): ?>
                    <?php foreach ($data->current->fields as $k => $i) if (!empty($i)): ?>
                        <p>
                            <?php if (isset($i->name)): ?><?= $i->name ?>:<?php endif ?>
                            <?= $i->value ?><?php if (isset($i->suffix)): ?><?= $i->suffix ?><?php endif ?>
                        </p>
                    <?php endif ?>
                <?php else: ?>
                    Off
                <?php endif ?>
            </h1>
            <p>Updated at <?= $data->current->date ?></p>
        </div>
        <?php foreach ($data->dev->get('dates') as $k => $i): ?>
            <?php if (!empty($i)): ?>
                <center style="color:<?= $data->dev->class()::COLORS[$k] ?>">
                    Last&nbsp;<?= $data->dev->class()::STATUSES[$k] ?>:
                    <?= date_create($i)->format('Y.m.d\&\n\b\s\p;H:i') ?>
                    (<?= date_create()->diff(date_create($i))->format('%ad&nbsp;%H:%I') ?>&nbsp;ago).
                </center>
            <?php endif ?>
        <?php endforeach ?>
    </section>

    <section id="chart">
        <form id="dates" action="/" method="get">
            <a class="left" href="?<?= $data->urls->back ?>">
                <svg width="1.5em" height="1.5em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.15" d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" fill="#777" />
                    <path d="M7 12H17M7 12L11 8M7 12L11 16M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#aaa" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </a>
            <div id="presets">
                <?php foreach ($data->urls->buttons as $name => $url): ?>
                    <input type="button" value="<?= $name ?>" onclick="window.location.href='?<?= $url ?>'">
                <?php endforeach ?>
            </div>
            <input name="chart" type="hidden" value="<?= $data->dev->get('name') ?>">
            <input name="from" type="datetime-local" value="<?= $data->from ?>">
            <span>-</span>
            <input name="to" type="datetime-local" value="<?= $data->to ?>">
            <input type="submit" value="Go">
            <a class="right" href="?<?= $data->urls->fwd ?>">
                <svg width="1.5em" height="1.5em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.15" d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" fill="#777" />
                    <path d="M17 12H7M17 12L13 16M17 12L13 8M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#aaa" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </a>
        </form>

        <?php if (empty($data->ranges)): ?>
            <div id="empty">No data!</div>
        <?php else: ?><br>
            <?php foreach ($data->ranges as $k => $r): ?>
                <center style="color:<?= $data->chart->colors[$k] ?>">
                    <?= $r->title ?>: <?= $r->min ?> - <?= $r->max ?><?php if (!empty($r->on)): ?>,
                    on: <?= $r->on ?><?php endif ?><?php if (!empty($r->off)): ?>,
                    off: <?= $r->off ?><?php endif ?>.
                </center>
            <?php endforeach ?>
            <canvas></canvas>
            <script>
                let c = new Chart(<?= json_encode($data->chart) ?>);
                c.dateStyles = {
                    year: {
                        month: 'numeric',
                        day: 'numeric'
                    },
                    month: {
                        month: 'numeric',
                        day: 'numeric'
                    },
                    day: {
                        hour: 'numeric',
                        hour12: false
                    },
                    hour: {
                        hour: 'numeric',
                        minute: 'numeric',
                        hour12: false
                    },
                    minute: {
                        hour: 'numeric',
                        minute: 'numeric',
                        hour12: false
                    },
                    tooltip: {
                        year: 'numeric',
                        month: 'numeric',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: 'numeric',
                        hour12: false
                    }
                };
                c.render();
            </script>
        <?php endif ?>
    </section>
</body>

</html>
