<?php

$data = \Helpers\Html::getVoltageData($_GET);

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients</title>
    <style>
        body {
            background-color: #161616;
            height: 100%;
            margin: 0;
            padding: 0;
            color: lightgray;
            top: 0;
        }

        section {
            position: relative;
            width: 80vw;
            margin: 3em auto;
        }

        a {
            color: #777;
        }

        input,
        button {
            background-color: #333;
            color: lightgray;
            border-color: #555;
        }

        #back {
            position: absolute;
            top: 0;
            left: 0;
        }

        #current #status {
            width: fit-content;
            max-width: 80vw;
            margin: 1em auto;
            padding: 1.5em 5em;
            text-align: center;
            border-radius: 1em;
        }

        #current #status * {
            margin: 0;
        }

        #current #status *+p {
            margin-top: 1em;
        }

        #status {
            width: auto;
            margin: 1em auto;
            padding: 10px 30px;
            text-align: center;
        }

        #empty {
            color: red;
            text-align: center;
            margin-top: 3em;
        }

        #dates {
            width: auto;
            margin: 1em auto;
            text-align: center;
        }

        #dates * {
            margin: auto 1em;
            padding: 5px;
        }

        #dates a {
            text-decoration: none;
        }

        #dates input[type=submit],
        #dates input[type=button] {
            padding: 5px 1.5em;
        }

        #dates span {
            margin: 0;
            padding: 0;
        }

        #chart canvas {
            width: 100%;
            aspect-ratio: 16/7;
            margin: 3em auto 1em;
            border-radius: 5px;
            display: block;
        }

        @media screen and (max-width: 700px) {
            #chart canvas {
                aspect-ratio: 3/4;
            }

            #dates {
                margin: 1em 3em;
                position: relative;
            }

            #dates * {
                display: block;
                margin: 1em auto;
            }

            #dates a {
                position: absolute;
                top: 50%;
                transform: translate(0, -100%);
            }

            #dates a:first-child {
                left: -2em;
            }

            #dates a:last-child {
                right: -2em;
            }

            #dates span {
                display: none;
            }
        }

        @media screen and (max-width: 300px) {
            #dates {
                margin: 1em 1em;
            }

            #dates a:first-child {
                left: 0;
            }

            #dates a:last-child {
                right: 0;
            }
        }
    </style>
    <script src="chart.min.js"></script>
</head>

<body id="body" class="dark">


    <section id="current">
        <a id="back" href="/">🡴 Back</a>
        <div id="status" style="background-color:<?= $data['color'] ?>">
            <h1><?= $data['current']['voltage'] ?>V</h1>
            <p>Updated at <?= $data['current']['date'] ?></p>
        </div>
    </section>

    <section id="chart">
        <form id="dates" action="/" method="get">
            <a href="?<?= $data['urls']['back'] ?>">🢀</a>
            <input type="button" value="Today" onclick="window.location.href='?<?= $data['urls']['now'] ?>'">
            <input name="chart" type="hidden" value="<?= $data['cfg'] ?>">
            <input name="from" type="datetime-local" value="<?= $data['from'] ?>">
            <span>-</span>
            <input name="to" type="datetime-local" value="<?= $data['to'] ?>">
            <input type="submit" value="Go">
            <a href="?<?= $data['urls']['fwd'] ?>">🢂</a>
        </form>

        <?php if (empty($data['chart']['layers']['1']['data'])): ?>
            <div id="empty">No data!</div>
        <?php else: ?>
            <canvas></canvas>
            <script>
                let c = new Chart(<?= json_encode($data['chart']) ?>);
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
