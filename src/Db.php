<?php

namespace Liar\Liar;
use \PDO;

class Db {
   public $handle;
   public $dbname;
   protected $tables;
   protected $queryEngine;

   public function __construct($dbname, QueryEngineProvider $credentials) {
      $this->dbname=$dbname;
      $this->queryEngine = $credentials->queryEngine($dbname);
      $this->tables=array();
   }
   
   /** 
     * invoke a table name as if a method, returning a new table definition object
     *
     * @param string $tablename
     * @param array $args
     */
   public function __call($tablename, $args) {
      if (array_key_exists($tablename, $this->tables)) return $this->tables[$tablename];

      $qe = $this->queryEngine;

      $cols = $qe("
         SELECT column_name, data_type, column_type, IFNULL(character_maximum_length, numeric_precision) as size
           FROM information_schema.columns WHERE table_schema = '{$this->dbname}' AND table_name = '{$tablename}'
      ");

      $this->tables[$tablename] = new Table($tablename, $cols);
      return $this->tables[$tablename];
   }
}
