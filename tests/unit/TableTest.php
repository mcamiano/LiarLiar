<?php

use Liar\Liar\Table;

class TableTest extends \Codeception\TestCase\Test
{
   use Codeception\Specify;

   /**
    * @var \UnitTester
    */
    protected $tester;

    protected function _before() {
        $this->table = new Table("foo", array(
             array('column_name'=>'id', 'data_type'=>'int', 'column_type'=>'int', 'size'=>11),
             array('column_name'=>'name', 'data_type'=>'varchar', 'column_type'=>'varchar[20]', 'size'=>20),
          ));
    }

    protected function _after() { }

    // tests
    public function testTable()
    {  
       $this->specify('it should enumerate its columns', function() {
          verify(count($columns = $this->table->columns()))->equals(2);
          verify($columns['id']['column_name'])->equals('id');
       });

       $this->specify('it should enumerate its column names', function() {
          verify(count($columnnames = $this->table->columnNames()))->equals(2);
          verify($columnnames)->contains('id');
          verify($columnnames)->contains('name');
       });

       $this->specify('it should be able to format a SQL Insert statement', function() {
          $sql = $this->table->formatSQLInsert(array('name'=>'Fluggel Duffel', 'id'=>1138));
          verify($sql)->equals("INSERT INTO foo (id,name) VALUES (1138,'Fluggel Duffel')");
       });

       $this->specify('it should be able to declare a new column', function() {
          $this->table->declareColumn( 'bar', 'text', 'text', 65535);
          $sql = $this->table->formatSQLInsert(array('name'=>'Fluggel Duffel', 'id'=>1138, 'bar'=>'Lorem Ipsum'));
          verify($sql)->equals("INSERT INTO foo (id,name,bar) VALUES (1138,'Fluggel Duffel','Lorem Ipsum')");
       });

       $this->specify('it should be able to format a comma-separated list of column names', function() {
          $this->table->declareColumn( 'baz', 'int', 'int', 65535);
          $list = $this->table->formatColumnList();
          verify($list)->equals('id,name,baz');
       });

       $this->specify('it should be able to format a comma-separated list of column values', function() {
          $this->table->declareColumn( 'boo', 'varchar', 'varchar(32)', 32);
          $list = $this->table->formatValueList(array('name'=>'Fluggel Duffel', 'id'=>1138, 'boo'=>'Lorem Ipsum', 'junk'=>'anything'));
          verify($list)->equals("1138,'Fluggel Duffel','Lorem Ipsum'");
       });
    }
}
