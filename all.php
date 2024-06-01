<?php

include 'bootstrap.php';


foreach (Config::CFG_TYPES as $type)
    ucfirst($type)::checkAll();
