<?php

use Liar\Liar\LiarLiar;
use Liar\Liar\TableSerializer;

class TableSerializerTest extends \Codeception\TestCase\Test
{
   use Codeception\Specify;
   var $table;
   var $serializableData;
   var $serializer;

   /**
    * @var \UnitTester
    */
    protected $tester;

    protected function _before() {
        $this->table = new stdClass();
        $this->table->name = 'foo';
        $cols = array(
            array('column_name'='prop1', 'data_type'=>'varchar', 'column_type'=>'varchar(10)', 'size'=>10 ),
            array('column_name'='prop2', 'data_type'=>'int', 'column_type'=>'int(4)', 'size'=>5 ),
        );
        $this->table->columns = function() use ($cols) {  return $cols; };
        $this->table->columnNames = function() use ($cols) { return array_keys($cols); };

        $this->table2 = new stdClass();
        $this->table2->name = 'bar';
        $cols2 = array(
            array('column_name'='prop3', 'data_type'=>'varchar', 'column_type'=>'varchar(255)', 'size'=>255 ),
            array('column_name'='prop4', 'data_type'=>'text', 'column_type'=>'text', 'size'=>5000 ),
        );
        $this->table2->columns = function() use ($cols2) {  return $cols2; };
        $this->table2->columnNames = function() use ($cols2) { return array_keys($cols2); };


        $serializableData = array(
           array( 
             array('id'     => 1),
             array('prop1'   => 'somevalue'),
             array('prop2'   => 'other value'),
             array('prop3'   => "Long Text Value-ish
                                 thing with newlines")
           ),
           array( 
             array('id'     => 2),
             array('prop1'   => 'another value'),
             array('prop2'   => 'yet other value'),
             array('prop3'   => "Some more Long Text Value-ish
                                 thing with newlines")
           ),
        );
        $this->table->rows = function() use ($serializableData) { return $serializableData; };

        $otherSerializableData = array(
           array( 
             array('id'     => 3),
             array('prop1'   => 'another value'),
             array('prop2'   => 'other value'),
             array('prop3'   => "Long Text Value-ish
                                 thing with newlines")
           ),
           array( 
             array('id'     => 4),
             array('prop1'   => 'yet another value'),
             array('prop2'   => 'yet other value'),
             array('prop3'   => "Some more Long Text Value-ish
                                 thing with newlines")
           ),
        );
        $this->table2->rows = function() use ($otherSerializableData) { return $otherSerializableData; };
    }

    protected function _after() { }

    // tests
    public function testTableSerializer()
    {  
       $this->specify('it should emit SQL INSERT statements consistent with a table structure', function() {

          $serializer1 = new TableSerializer($this->table);
          $statement1  = $serializer1->render();
          $serializer2 = new TableSerializer($this->table2);
          $statement2  = $serializer2->render();

          verify($statement1)->notEquals($statement2);
          verify(strtoupper($statement1))->isKindOfLike("insert into table1 values *(id, *prop1, *prop2, *prop3) *('somevalue', *'othervalue', *'Long Text value-ish *thing with newlines' *)");
       });
    }
}
