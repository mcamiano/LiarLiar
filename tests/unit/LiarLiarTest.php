<?php

use Liar\Liar\LiarLiar;

class LiarLiarTest extends \Codeception\TestCase\Test
{
   use Codeception\Specify;

   /**
    * @var \UnitTester
    */
    protected $tester;

    public function dbconfig() {
        return array(
	    'db' => array(
		'default' => array(
		    'hostname' => "localhost",
		    'username' => "fakeuser",
		    'password' => "secret",
		    'database' => "testdb"
		),
		'infoschema' => array(
		    'hostname' => "localhost",
		    'username' => "fakeuser",
		    'password' => "secret",
		    'database' => "information_schema"
		)
	    )
        );
    }

    protected function _before() {
        $config=$this->dbconfig();
        $this->liar = new LiarLiar(
           $config['db']['default']['username'],
           $config['db']['default']['password']
        );
           // array(
             // array('column_name'=>'id', 'data_type'=>'int', 'column_type'=>'int', 'size'=>11),
             // array('column_name'=>'name', 'data_type'=>'varchar', 'column_type'=>'varchar[20]', 'size'=>20),
           // )
    }

    protected function _after() { }

    // tests
    public function testLiarLiar()
    {  
       $this->specify('it should not lie the same way twice', function() {
          $config=$this->dbconfig();
          $fakerecord1 = $this->liar->lieAbout($config['db']['default']['database'], 'master', array('id'));
          $fakerecord2 = $this->liar->lieAbout($config['db']['default']['database'], 'master', array('id'));
          $fakerecord3 = $this->liar->lieAbout($config['db']['default']['database'], 'master', array('id'));
          $fakerecord4 = $this->liar->lieAbout($config['db']['default']['database'], 'master', array('id'));

          verify($fakerecord1)->notEquals($fakerecord2);
          verify($fakerecord2)->notEquals($fakerecord3);
          verify($fakerecord3)->notEquals($fakerecord4);
       });

       $this->specify('it should weave a tangled web of lies, by incorporating foreign keys in generated relations', function() {
          $config=$this->dbconfig();

          $fakerecord1 = $this->liar->lieAbout($config['db']['default']['database'], 'master', $keys=array('id'));

          $loadForeignKeysFrom=array('master'=>array('master_id' => 'id', 'other_master_id'=>'id'));

          $fakerecord2 = $this->liar->lieAbout($config['db']['default']['database'], 'slave', array('id'), $loadForeignKeysFrom);

          $cachedKeys = $this->liar->keymaster;
          $master_id = $cachedKeys['master'][0]['id'];

            // verify that the master id key is placed into the slave foreign keys we specified
          $matches = preg_match("/INSERT INTO slave .* VALUES \([0-9]+,{$master_id},{$master_id},.*\)/", $fakerecord2);
          verify( $matches )->equals(1);
       });

       /*
       $this->specify('it should provide a fake record consistent with a table structure', function() {
          $config=$this->dbconfig();

          $fakerecord = $this->liar->lieAbout($config['db']['default']['database'], 'master', array('id'));

          $matches = preg_match("/INSERT INTO master \(.*\) VALUES \(.*\)/m",$fakerecord);

          verify( $matches )->equals(1);
       });
       */
    }
}
