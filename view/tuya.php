<?php

use Helpers\Html;

$data = Html::getVoltage();

if (empty($data['values'])) {
    header('Location: /');
    exit;
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients</title>
    <style>
        #dates {
            width: auto;
            margin: 1em auto;
            text-align: center;
        }

        #dates * {
            margin: auto 1em;
        }

        #chart {
            width: 80vw;
            height: 50vh;
            margin: 1em auto;
            border: 2px dotted black;
            border-radius: 5px;
            display: block;
        }
    </style>
    <script src="chart.min.js"></script>
</head>

<body id="body">

    <form id="dates" action="/" method="get">
        <input name="chart" type="hidden" value="<?= $_GET['chart'] ?? '' ?>">
        <input name="from" type="datetime-local" value="<?= $data['from'] ?>">
        <input name="to" type="datetime-local" value="<?= $data['to'] ?>">
        <button>Go</button>
    </form>

    <canvas id="chart"></canvas>
    <script>
        let chart = new Chart({
            "canvas": '#chart',
            "title": "<?= $data['name'] ?? 'Chart' ?>",
            "dataType": "integer",
            "dataSuffix": " V",
            "locale": "uk-UA",
            "layers": [{
                "hidden": true
            }, {
                "title": "Voltage",
                "data": <?= json_encode($data['values']) ?>
            }]
        });
        chart.render();
    </script>

</body>

</html>
