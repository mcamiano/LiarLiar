<?php

namespace Liar\Liar;
use \PDO;

class Db {
   public $handle;
   public $dbname;
   protected $tables;

   public function __construct($dbname, $user, $pass) {
      $this->dbname=$dbname;
      $this->handle = new PDO('mysql:host=localhost;dbname='.$dbname, $user, $pass);
      $this->tables=array();
   }
   
   public function q($q,$closure=NULL) {
      if (is_callable($closure)) {
         $result = $this->handle->query($q);
         if (!$result) throw new Exception("Bad query $q");
         $result = $result->fetchAll(PDO::FETCH_ASSOC);
         return array_map($closure, $result);
      }
      return $this->handle->query($q)->fetchAll(PDO::FETCH_ASSOC);
   }

   /** 
     * invoke a table name as if a method, returning a new table definition object
     *
     * @param string $tablename
     * @param array $args
     */
   public function __call($tablename, $args) {
      if (array_key_exists($tablename, $this->tables)) return $this->tables[$tablename];

      $cols = $this->q("
         SELECT column_name, data_type, column_type, IFNULL(character_maximum_length, numeric_precision) as size
           FROM information_schema.columns WHERE table_schema = '{$this->dbname}' AND table_name = '$tablename'
      ");
      $this->tables[$tablename] = new Table($tablename, $cols);
      return $this->tables[$tablename];
   }
}
