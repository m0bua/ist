<?php $data = \Helpers\Html::getVoltageData($_GET) ?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['name'] ?> Chart</title>
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
            top: -1.7em;
            left: 0;
        }

        #back svg {
            position: absolute;
            top: .1em;
            left: -1.2em;
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
            line-height: 1;
        }

        #dates a {
            display: inline-block;
            text-decoration: none;
            vertical-align: middle;
        }

        #dates a svg {
            margin: 0;
            padding: 0;
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

        @media screen and (max-width: 980px) {
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
        <div id="status" style="background-color:<?= $data['color'] ?>">
            <h1><?= $data['current']['online'] === 'true' ? $data['current']['voltage'] . 'V' : 'Offline' ?></h1>
            <p>Updated at <?= $data['current']['date'] ?></p>
        </div>
    </section>

    <section id="chart">
        <form id="dates" action="/" method="get">
            <a class="left" href="?<?= $data['urls']['back'] ?>">
                <svg width="1.5em" height="1.5em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.15" d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" fill="#777" />
                    <path d="M7 12H17M7 12L11 8M7 12L11 16M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#aaa" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </a>
            <input type="button" value="Today" onclick="window.location.href='?<?= $data['urls']['now'] ?>'">
            <input name="chart" type="hidden" value="<?= $data['cfg'] ?>">
            <input name="from" type="datetime-local" value="<?= $data['from'] ?>">
            <span>-</span>
            <input name="to" type="datetime-local" value="<?= $data['to'] ?>">
            <input type="submit" value="Go">
            <a class="right" href="?<?= $data['urls']['fwd'] ?>">
                <svg width="1.5em" height="1.5em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.15" d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" fill="#777" />
                    <path d="M17 12H7M17 12L13 16M17 12L13 8M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#aaa" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </a>
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
