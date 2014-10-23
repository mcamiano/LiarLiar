<?php

namespace Liar\Liar;

use \Faker\Factory;

class LiarLiar {
   protected $fake;
   protected $pdos;
   protected $user;
   protected $pw;

      // buffers up the interesting fields we just created, in case we need to reference them for some other relation
   public $keymaster;

   public function __construct($user='FAKEUSER', $pw='FAKEPASSWORD') {
      $this->pdos=array();
      $this->fake = \Faker\Factory::create();
      $this->user=$user;
      $this->pw=$pw;
      $this->keymaster=array(); // indexed by table name, row #, column name
   }

   public function __call($dbname,$args) {
      if (array_key_exists($dbname, $this->pdos)) return $this->pdos[$dbname];
      else return $this->pdos[$dbname] = new Db($dbname,$this->user,$this->pw);
   }

/*
      return $this->pdo->q(
 function($row) {
         $expressions=array();
         foreach(array_keys($row) as $key) {
            $expressions[] = "$key='".PDO::quote($row[$key])."'";
         }
         return "INSERT INTO $implode(',',$expressions).';';
      });
*/

   public function MpeopleDataPeople($keyfields=NULL) {
      if (!array_key_exists('people',$this->keymaster)) $this->keymaster['people'] = array();
      if (is_null($keyfields)) $keyfields=array();

      $keys=array();
      $fields = $this->MpeopleData()->people();
      $expressions=array();
      foreach($fields as $field) {
         $method="fake_".$field['data_type'];
         $expressions[$field['column_name']] = $this->$method($field);
         if (in_array($field['column_name'], $keyfields)) {
             $keys[$field['column_name']] = $expressions[$field['column_name']];
         }
      }

      $this->keymaster['people'][] = $keys; // buffer up the interesting fields we just created, in case we need to reference them for some other relation

      return "INSERT INTO people (".implode(',',array_keys($expressions)).") VALUES (".implode("', '",$expressions)."')\n";
   }

   public function fake_varchar($field) { return ('REPLACE WITH FAKER DATA'); }
   public function fake_int($field) { return ('REPLACE WITH FAKER DATA'); }
   public function fake_enum($field) { return ('REPLACE WITH FAKER DATA'); }
   public function fake_text($field) { return ('REPLACE WITH FAKER DATA'); }
   public function fake_tinytext($field) { return ('REPLACE WITH FAKER DATA'); }

/*
          "title"=>preg_replace( "/\s+/", " ", implode(' ',$fake->words(4))),
          "performance_ind"=>80+(int)$fake->randomDigit(),
          "threshold"=>80,
          "scale"=>100,
          "data_coursedata"=>$fake->numerify('##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##'),
          "instructor_comments"=>preg_replace( "/\s+/", " ", implode("\n",$fake->sentences($fake->randomDigit())))
*/

   public static function bist($user='root',$pw='') {
     $lieAbout = new LiarLiar($user, $pw);
     $people = $lieAbout->MpeopleDataPeople(array('uid'));
     echo var_dump($people);
     echo var_dump($lieAbout->keymaster);
   }
}
