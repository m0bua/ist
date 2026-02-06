<?php

include '../bootstrap.php';

use Helpers\Html;

if (isset($_GET['d']) || isset($_GET['u'])) {
    Dev::createPoint($_GET['d'] ?? $_GET['u'], $_GET);
    exit;
} elseif ($_GET['format'] ?? null === 'json') {
    exit(Html::getClientsJson());
} elseif (isset($_GET['cfg']) && isset($_GET['name'])) {
    exit(Dev::create($_GET['name'])->change($_GET['cfg']));
} elseif (isset($_GET['chart'])) {
    include '../view/tuya.php';
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients</title>
    <link rel=stylesheet type="text/css" href="res/index.css">
    <script src="res/clients.js"></script>
</head>

<body id="body"></body>

</html>
