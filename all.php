<?php

include 'bootstrap.php';

Helper::popenAll(in_array(($argv[1] ?? null), ['p', 'parallel']));
