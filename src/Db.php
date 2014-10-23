<?php

namespace Liar\Liar;
use \PDO;

class Db {
   public $handle;
   public $dbname;

   public function __construct($dbname, $user, $pass) {
      $this->dbname=$dbname;
      $this->handle = new PDO('mysql:host=localhost;dbname='.$dbname, $user, $pass);
   }
   
   public function q($q,$closure=NULL) {
      if (is_callable($closure)) return array_map($this->handle->query($q), $closure);
      return $this->handle->query($q);
   }

   public function __call($tablename, $args) {
      return $this->q("
         SELECT column_name, data_type, column_type, IFNULL(character_maximum_length, numeric_precision) as size
           FROM information_schema.columns WHERE table_schema = '{$this->dbname}' AND table_name = '$tablename';
      ");
   }
}
