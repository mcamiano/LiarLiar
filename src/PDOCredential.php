<?php

namespace Liar\Liar;

use \PDO;

class PDOCredential implements QueryEngineProvider {
   public $username;
   public $password;
   public $dsnprovider;

   public function __construct($dsnprovider, $username, $password) {
      $this->dsnprovider = $dsnprovider;
      $this->username = $username;
      $this->password = $password;
   }

   public function queryEngine($dbname) {
      $provider = $this->dsnprovider;
      $dsn = $provider($dbname);

      $handle = new \PDO($dsn, $this->username, $this->password);

      return function($q,$closure=NULL) use ($handle) {
         if (is_callable($closure)) {
            $result = $handle->query($q);
            if (!$result) throw new \Exception("Bad query $q");
            $result = $result->fetchAll(PDO::FETCH_ASSOC);
            return array_map($closure, $result);
         }
         return $handle->query($q)->fetchAll(PDO::FETCH_ASSOC);
      };
   }
}

