<?php

include 'bootstrap.php';

Dev::runAll(in_array(($argv[1] ?? null), ['p', 'parallel']));
