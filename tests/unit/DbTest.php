<?php

use Liar\Liar\Db;

class DbTest extends PHPUnit_Framework_TestCase {

    use Codeception\Specify;


    protected function setUp() {
        $this->db = new Db('testdb', 'root', '');
    }


    public function testDbConnection()
    {
        $this->specify('it should connect to a database normally', function() {
            $db = new Db('testdb', 'root', '');
        });      

        $this->specify('it should not connect to a database with bad info ', function() {
            $db = new Db('thisdb-doesnot-exist', 'some fake user', 'some fake password');
        }, [
            'throws' => new \PDOException
        ]);      

        $this->specify('it supports a method to access the db for queries', function() {
            $result = $this->db->q('SELECT * FROM master', function($row) { return array_keys($row); });
            verify($result)->contains('id');
            verify($result)->contains('name');
            verify($result)->contains('created_on');
            verify($result)->contains('enumerated_value');
            verify($result)->contains('long_field');
        });

        $this->specify('it supports a method to access the column properties for a table ', function() {
            $columns = $this->db->master();
            verify(length($columns))->equals('5');
        }
    }
}
