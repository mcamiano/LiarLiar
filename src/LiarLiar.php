<?php

namespace Liar\Liar;

use \Faker\Factory;
use \DateTime;

class LiarLiar {
   protected $databases;
   protected $credential;
   protected $autoincrementBase;
   protected $autoincrementId;
   protected $serializerProvider;
   protected $faker;

      // buffers up the interesting fields we just created, in case we need to reference them for some other relation
   public $keymaster;
   public $hint;

   public function __construct(QueryEngineProvider $credential, \Closure $serializerProvider, $autoid=1000) {
      $this->databases=array();
      $this->serializerProvider=$serializerProvider;
      $this->faker = \Faker\Factory::create();
      $this->credential=$credential;
      $this->keymaster=array(); // indexed by table name, row #, column name
      $this->hint=array(); // indexed by table name, column name
      $this->resetAutoincrement($autoid);
   }

   /** 
     * Delegate an undefined method call, giving a proxy Db object to a PDO database connection, aggregating tables
     */
   public function __call($dbname,$args) {
      if (array_key_exists($dbname, $this->databases)) return $this->databases[$dbname];
      else return $this->databases[$dbname] = new Db($dbname,$this->credential);
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
         foreach( $foreignKeys as $fkey => $fkeySource ) {
            list($sourcetable,$sourcecolumn) = explode('.',$fkeySource);
            if (empty($sourcecolumn)||empty($sourcetable)) throw new \Exception('Malformed foreign key reference in config: '.$fkeySource);
            $remapForeignKeys[$fkey] = $this->sampleCachedKey($sourcetable,$sourcecolumn);
         }
      }

      $keys=array();
      $db = $this->$database();
      $table = $db->$tablename();
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
      $table->insert($expressions);

      $sp = $this->serializerProvider;
      $serializer = $sp( $table );

      return $serializer->render();
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


   public function fake_varchar($field) { return $this->faker->text( intval($field['size']) ); }

   public function fake_int($field) { return $this->faker->randomNumber(6) % $field['size']; }

   public function fake_enum($field) {
      $picks=array_map(function($v) { return trim($v,"' "); }, explode(',',preg_replace('/(^enum\(|\)$)/','',$field['column_type'])));
      return $picks[$this->faker->randomDigit % count($picks) ];
   }

   public function fake_text($field) { return $this->faker->text( intval($field['size']) % 2048 ); }

   public function fake_tinytext($field) { return $this->faker->text( intval($field['size'])); }

   // this should just be automatically mapped to Faker calls
   public function fake_date($field) { return $this->faker->date(); }
   public function fake_dateTime($field) { $dt = $this->faker->dateTime(); return $dt->format('Y-m-d H:i:s'); }
   public function fake_time($field) { return $this->faker->time(); }
   public function fake_year($field) { return $this->faker->year(); }
   public function fake_month($field) { return $this->faker->month(); }
   public function fake_monthName($field) { return $this->faker->monthName(); }
   public function fake_name($field) { return $this->faker->name(); }
   public function fake_firstName($field) { return $this->faker->firstName(); }
   public function fake_lastName($field) { return $this->faker->lastName(); }
   public function fake_paragraph($field) { return $this->faker->paragraph(); }
   public function fake_word($field) { return $this->faker->word(); }
   public function fake_words($field) { return implode(' ',$this->faker->words()); }
   public function fake_sentence($field) { return $this->faker->sentence(); }
   public function fake_state($field) { return $this->faker->state(); }
   public function fake_stateAbbr($field) { return $this->faker->stateAbbr(); }
   public function fake_streetAddress($field) { return $this->faker->streetAddress(); }
   public function fake_city($field) { return $this->faker->city(); }
   public function fake_postcode($field) { return $this->faker->postcode(); }
   public function fake_phoneNumber($field) { return $this->faker->phoneNumber(); }
   public function fake_safeEmail($field) { return $this->faker->safeEmail(); }
   public function fake_email($field) { return $this->faker->email(); }
   public function fake_url($field) { return $this->faker->url(); }
   public function fake_userName($field) { return $this->faker->userName(); }
   public function fake_title($field) { return $this->faker->title(); }

   // aliases
   public function fake_zip($field) { return $this->faker->postcode(); }

   public function fake_unityid($field) { return $this->faker->lexify('????????'); }
   public function fake_ou($field) { return $this->faker->numerify('#####'); }
   public function fake_AAANNN($field) { return $this->faker->bothify('???###'); }

   public function fake_autoincrement($field) { return ++$this->autoincrementId; }

   public function fake_fullName($field) { return $this->faker->firstName() . ' ' . $this->faker->lastName(); }
   public function fake_fullAddress($field) { return $this->faker->streetAddress() . ' ' . $this->faker->city() . ' ' . $this->faker->state() . ' ' . $this->faker->postcode();  }
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

    /**
     * bist: built-in self-test
     *
     */
   public static function bist($user='root',$pw='', $n=10) {
     $d=new DateTime(); 

     $INSERTSerializerProvider = function($table) { return new TableInsertSerializer( $table ); };
     $CSVSerializerProvider = function($table) { return new CSVSerializer( $table ); };

     $liar = new LiarLiar( 
        new PDOCredential( function($dbname){return "mysql:host=localhost;dbname=$dbname";}, $user, $pw),
        // $CSVSerializerProvider 
        $INSERTSerializerProvider 
     );

     $liar->typeHint('master','id','autoincrement');
     $liar->typeHint('slave','id','autoincrement');

     $liar->resetAutoincrement( $d->getTimestamp() % 100000 );
     for ($i=0; $i<$n; ++$i) {
         $master = $liar->lieAbout('testdb', 'master', array('id'));
         echo $master."\n";
     }

     $liar->resetAutoincrement( $d->getTimestamp() % 1000 );
     for ($i=0; $i<$n; ++$i) {
         $slave = $liar->lieAbout('testdb', 'slave', $keys=array('id'), $fkeys=array('master'=>array('master_id' => 'id', 'other_master_id'=>'id')) );
         echo $slave."\n";
     }
     // echo var_dump($liar->keymaster);
   }
}
