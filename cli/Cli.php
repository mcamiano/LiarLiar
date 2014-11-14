<?php

require_once('../vendor/autoload.php');

use Liar\Liar\Db;
use Liar\Liar\LiarLiar;
use Liar\Liar\PDOCredential;
use Liar\Liar\CSVSerializer;
use Liar\Liar\TableInsertSerializer;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

class Cli 
{
  public $serializerProvider;
  public $username;
  public $password;
  public $liarHints;
  public $tables;

  public function __construct($configfile='./config.yml') 
  {
    $this->tables=array();
    $this->liarHints=array();

    $yaml = new Parser();

    try {
        if (! $config = @$yaml->parse(file_get_contents($configfile)) ) {
           throw new \Exception("Your config file is empty or malformed. Bailing out...\n");
        }

        foreach ( $config as $topLevelKey => $properties ) {
          $method=$topLevelKey."Config";
          if (method_exists($this,$method)) {
             $this->$method($properties);
          }
        }
    } catch (ParseException $e) {
        throw new \Exception("Your config file is malformed. Bailing out...\n");
    }
  }

  protected function defaultRowCount()
  {
     return 5;
  }

  protected function serializerConfig($serializerConfig)
  {
    switch (strtoupper($serializerConfig)) {
    case 'CSV':
      $this->serializerProvider = function($table) { return new CSVSerializer( $table ); };
    break;
    case 'SQL':
    default:
      $this->serializerProvider = function($table) { return new TableInsertSerializer( $table ); };
    break;
    }
  }

  protected function dbConfig($dbConfig)
  {
    if (! (isset($dbConfig['default']) && isset($dbConfig['default']['username']) && isset($dbConfig['default']['password']))
    ) throw new \Exception("Configuration must specify a default database connection");

    $this->username = $dbConfig['default']['username'];
    $this->password = $dbConfig['default']['password'];
  }

  protected function tablesConfig($tablesConfig)
  {
    $this->liarHints=array();

    foreach( $tablesConfig as $tablename => $properties ) {

      // provide defaults for config
      $this->tables[$tablename] = array(
         'count'=>( isset($properties['count'])?$properties['count'] : $this->defaultRowCount()),
         'keep-samples'=>( isset($properties['keep-samples']) ? $properties['keep-samples'] : array() ),
         'foreign-keys'=>( isset($properties['foreign-keys']) ? $properties['foreign-keys'] : array() )
      );

      foreach( $properties['hints'] as $column => $hint ) {
        $this->liarHints[] = function($liar) use($tablename, $column, $hint) { return $liar->typeHint($tablename, $column, $hint);};
      }
    }
  }

  protected function makeLiar()
  {
    $liar = new LiarLiar( 
       new PDOCredential( 
         function($dbname) { return "mysql:host=localhost;dbname=$dbname";},
         $this->username, 
         $this->password
       ),
       $this->serializerProvider 
    );

    $d=new DateTime();
    $liar->resetAutoincrement( $d->getTimestamp() % 100000 );
 
    foreach($this->liarHints as $giveHintTo) { $giveHintTo($liar); }

    return $liar;
  }

  public function lie() 
  {
    $liar = $this->makeLiar();

    foreach( $this->tables as $tablename => $properties ) {
      for ($i=0; $i<$properties['count']; ++$i) {
        $result = $liar->lieAbout('testdb', $tablename, $properties['keep-samples'], $properties['foreign-keys']);
        echo $result."\n";
      }
    }
    echo "\n";
  } 
}

$cli = new Cli();
$cli->lie();
