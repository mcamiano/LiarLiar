<?php

require_once('../vendor/autoload.php');

use Liar\Liar\Db;
use Liar\Liar\LiarLiar;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

class Cli 
{
  public function __construct($configfile='./config.yml') 
  {
    $yaml = new Parser();

    try {
        if (! $config = @$yaml->parse(file_get_contents($configfile)) ) {
           print "Your config file is empty or malformed. Bailing out...\n";
           exit(1);
        }

        LiarLiar::bist( $config['db']['default']['username'], $config['db']['default']['password'] );

        echo "\n";
    } catch (ParseException $e) {
        print "Your config file is malformed. Bailing out...\n";
        exit;
    }
  }
}

new Cli();
