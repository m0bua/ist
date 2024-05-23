<?php

include 'bootstrap.php';

exit((int)(new Direct($argv))->status());
