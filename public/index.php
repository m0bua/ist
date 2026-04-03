<?php

include '../bootstrap.php';

use Helpers\Html;

if (isset($_GET['d']) || isset($_GET['u'])) {
    Dev::createPoint($_GET['d'] ?? $_GET['u'], $_GET);
} elseif ($_GET['format'] ?? null === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo Html::getClientsJson();
} elseif (isset($_GET['cfg']) && isset($_GET['name'])) {
    Dev::create($_GET['name'])->change($_GET['cfg']);
} elseif (isset($_GET['chart'])) {
    include '../view/chart.php';
} else {
    include '../view/clients.php';
}
