<?php

require_once('../vendor/autoload.php');

use Liar\Liar\Db;
use Liar\Liar\LiarLiar;

$config = require_once('config.php');

LiarLiar::bist( $config['db']['default']['username'], $config['db']['default']['password'] );
echo "\n";
