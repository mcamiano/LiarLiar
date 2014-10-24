<?php

namespace Liar\Liar;

use \Faker\Factory;
use \DateTime;

class LiarLiar {
   protected $faker;
   protected $pdos;
   protected $user;
   protected $pw;
   protected $autoincrementBase;
   protected $autoincrementId;

      // buffers up the interesting fields we just created, in case we need to reference them for some other relation
   public $keymaster;
   public $hint;

   public function __construct($user='FAKEUSER', $pw='FAKEPASSWORD', $autoid=1000) {
      $this->pdos=array();
      $this->faker = \Faker\Factory::create();
      $this->user=$user;
      $this->pw=$pw;
      $this->keymaster=array(); // indexed by table name, row #, column name
      $this->hint=array(); // indexed by table name, column name
      $this->resetAutoincrement($autoid);
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
     * @param string $database
     * @param string $tablename
     * @param array $keyfields
     * @param array[] $foreignKeys  ex: array('master'=>array('master_id' => 'id', 'other_master_id'=>'id'));
     */
   public function lieAbout($database, $tablename, $keyfields=NULL, $foreignKeys=NULL) {
      if (!array_key_exists($tablename,$this->keymaster)) $this->keymaster[$tablename] = array();
      $hints = array();
      if (array_key_exists($tablename, $this->hint)) {
          $hints = $this->hint[$tablename];
      }

      if (is_null($keyfields)) $keyfields=array();

      // Sample any foreign keys needed to establish relationships
      $remapForeignKeys=array();
      if (is_array($foreignKeys)) {
         foreach( $foreignKeys as $table => $fkeys ) {
             foreach( $fkeys as $fkey => $fkeySource ) {
                 $remapForeignKeys[$fkey] = $this->sampleCachedKey($table,$fkeySource);
             }
         }
      }

      $keys=array();
      $table = $this->$database()->$tablename();
      $fields = $table->columns();

      $expressions=array();

      // Generate fake data, substituting in foreign key values where they exist
      foreach($fields as $field) {
         $fieldname = $field['column_name'];

         if (array_key_exists($fieldname, $remapForeignKeys)) {
             $expressions[$fieldname] = $remapForeignKeys[$fieldname];
         } else {
             $method="fake_".$field['data_type'];

             if (array_key_exists($field['column_name'], $hints)) {
                 $method="fake_".$hints[ $field['column_name'] ];
             }
             $expressions[$field['column_name']] = $this->$method($field);
         }

         if (in_array($field['column_name'], $keyfields)) {
             $keys[$field['column_name']] = $expressions[$field['column_name']];
         }
      }

      $this->keymaster[$tablename][] = $keys; // buffer up the interesting fields we just created, in case we need to reference them for some other relation
      return $table->formatSQLInsert($expressions);
   }

   public function resetAutoincrement($newbase=1000) {
      $this->autoincrementBase = $newbase;
      $this->autoincrementId = $this->autoincrementBase + rand( 0, 1000 );
   }

   /**
     * Sample a single key value from previously generated values.
     * @param string $table
     * @param string $field
     */
   public function sampleCachedKey($tablename, $fieldname) {
       $keylist = $this->keymaster[$tablename];
       return $keylist[rand(0,count($keylist)-1)][$fieldname];
   }

   public function fake_date($field) { $dt = $this->faker->dateTime(); return $dt->format('Y-m-d H:i:s'); }

   public function fake_varchar($field) { return $this->faker->text( intval($field['size']) ); }

   public function fake_int($field) { return $this->faker->randomNumber(6) % $field['size']; }

   public function fake_enum($field) {
      $picks=array_map(function($v) { return trim($v,"' "); }, explode(',',preg_replace('/(^enum\(|\)$)/','',$field['column_type'])));
      return $picks[$this->faker->randomDigit % count($picks) ];
   }

   public function fake_text($field) { return $this->faker->text( intval($field['size']) % 2048 ); }

   public function fake_tinytext($field) { return $this->faker->text( intval($field['size'])); }

   public function fake_autoincrement($field) { return ++$this->autoincrementId; }

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

   public static function bist($user='root',$pw='', $n=10) {
     $d=new DateTime(); 

     $liar = new LiarLiar($user, $pw);

     $liar->typeHint('master','id','autoincrement');
     $liar->typeHint('slave','id','autoincrement');

     $liar->resetAutoincrement( $d->getTimestamp() % 100000 );
     for ($i=0; $i<$n; ++$i) {
         $master = $liar->lieAbout('testdb', 'master', array('id'));
         echo $master.";\n";
     }

     $liar->resetAutoincrement( $d->getTimestamp() % 1000 );
     for ($i=0; $i<$n; ++$i) {
         $slave = $liar->lieAbout('testdb', 'slave', $keys=array('id'), $fkeys=array('master'=>array('master_id' => 'id', 'other_master_id'=>'id')) );
         echo $slave.";\n";
     }
     // echo var_dump($liar->keymaster);
   }
}
