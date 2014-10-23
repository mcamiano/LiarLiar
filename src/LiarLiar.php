<?php

namespace Liar\Liar;

use \Faker\Factory;

class LiarLiar {
   protected $faker;
   protected $pdos;
   protected $user;
   protected $pw;

      // buffers up the interesting fields we just created, in case we need to reference them for some other relation
   public $keymaster;
   public $hint;

   public function __construct($user='FAKEUSER', $pw='FAKEPASSWORD') {
      $this->pdos=array();
      $this->faker = \Faker\Factory::create();
      $this->user=$user;
      $this->pw=$pw;
      $this->keymaster=array(); // indexed by table name, row #, column name
      $this->hint=array(); // indexed by table name, column name
   }

   /** 
     * Delegate an undefined method call, giving a proxy Db object to a PDO database connection, aggregating tables
     */
   public function __call($dbname,$args) {
      if (array_key_exists($dbname, $this->pdos)) return $this->pdos[$dbname];
      else return $this->pdos[$dbname] = new Db($dbname,$this->user,$this->pw);
   }

   /** 
     * Generate fake data for a database table, with hints for remembering key fields, and hints for populating foreign keys
     */
   public function lieAbout($database, $tablename, $keyfields=NULL) {
      if (!array_key_exists($tablename,$this->keymaster)) $this->keymaster[$tablename] = array();
      $hints = array();
      if (array_key_exists($tablename, $this->hint)) {
          $hints = $this->hint[$tablename];
      }

      if (is_null($keyfields)) $keyfields=array();

      $keys=array();
      $table = $this->$database()->$tablename();
      $fields = $table->columns();

      $expressions=array();

      foreach($fields as $field) {
         $method="fake_".$field['data_type'];

         if (array_key_exists($field['column_name'], $hints)) {
             $method="fake_".$hints[ $field['column_name'] ];
         }

         $expressions[$field['column_name']] = $this->$method($field);

         if (in_array($field['column_name'], $keyfields)) {
             $keys[$field['column_name']] = $expressions[$field['column_name']];
         }
      }

      $this->keymaster[$tablename][] = $keys; // buffer up the interesting fields we just created, in case we need to reference them for some other relation
      return "INSERT INTO $tablename (".implode(',',array_keys($expressions)).") VALUES (".implode("', '",$expressions)."')\n";
   }

   public function fake_varchar($field) { return $this->faker->text( intval($field['size']) ); }

   public function fake_int($field) { return $this->faker->randomNumber(6) % $field['size']; }

   public function fake_enum($field) {
      $picks=explode(',',preg_replace('/(^enum\(|\)$)/','',$field['column_type']));
      return $picks[$this->faker->randomDigit % count($picks) ];
   }

   public function fake_text($field) { return $this->faker->text( intval($field['size']) % 2048 ); }

   public function fake_tinytext($field) { return $this->faker->text( intval($field['size'])); }

/*
          "title"=>preg_replace( "/\s+/", " ", implode(' ',$fake->words(4))),
          "performance_ind"=>80+(int)$fake->randomDigit(),
          "threshold"=>80,
          "scale"=>100,
          "data_coursedata"=>$fake->numerify('##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##, ##'),
          "instructor_comments"=>preg_replace( "/\s+/", " ", implode("\n",$fake->sentences($fake->randomDigit())))
*/

   /**
     * Set a faker type hint for Table.column
     */
   public function typeHint($tablename, $fieldname, $hint) { 
       if (!array_key_exists($tablename, $this->hint)) $this->hint[$tablename] = array();
       $this->hint[$tablename][$fieldname] = $hint; // TODO verify hint has a correspondingfake_* method
   }

   public static function bist($user='root',$pw='') {
     $liar = new LiarLiar($user, $pw);
     $people = $liar->lieAbout('MpeopleData', 'people', array('uid'));
     echo $people;
     // echo var_dump($liar->keymaster);
   }
}
