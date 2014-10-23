<?php

use Liar\Liar\Db;

class DbTest extends \Codeception\TestCase\Test 
{
    use Codeception\Specify;

    protected function _before() { }
    protected function _after() { }

    public function testDbConnection()
    {
        $this->specify('it should connect to a database normally', function() {
            $db = new Db('testdb', 'fakeuser', 'secret');
        });      

        $this->specify('it supports a method to access the db for queries', function() {
            $somedb = new Db('testdb', 'fakeuser', 'secret');
            $result = $somedb->q('SELECT * FROM information_schema.ENGINES', function($row) { return array_keys($row); });
            verify($result[0])->contains('ENGINE');
        });

        $this->specify('it supports a method to access the column properties for a table ', function() {
            $somedb = new Db('testdb', 'fakeuser', 'secret');
            $table = $somedb->master();
            verify(count($table->columns()))->equals('5');
        });
    }
}
