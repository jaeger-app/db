<?php
/**
 * Jaeger
 *
 * @copyright	Copyright (c) 2015-2016, mithra62
 * @link		http://jaeger-app.com
 * @version		1.0
 * @filesource 	./tests/DbTest.php
 */
namespace JaegerApp\tests;

use JaegerApp\Db;

/**
 * JaegerApp - Db object Unit Tests
 *
 * Contains all the unit tests for the \mithra62\Db object
 *
 * @package JaegerApp\Tests
 * @author Eric Lamb <eric@mithra62.com>
 */
class DbTest extends \PHPUnit_Framework_TestCase
{

    public function testInit()
    {
        $db = new Db();
        $this->assertObjectHasAttribute('db', $db);
        $this->assertObjectHasAttribute('credentials', $db);
        $this->assertObjectHasAttribute('columns', $db);
        $this->assertObjectHasAttribute('where', $db);
    }

    public function testTable()
    {
        $db = new Db();
        $this->assertFalse($db->getTable());
        $this->assertInstanceOf('JaegerApp\Db', $db->from($this->test_table_name));
        $this->assertEquals($this->test_table_name, $db->getTable());
    }

    public function testWhere()
    {
        $db = new Db();
        $this->assertEquals('1=1', $db->getWhere());
        $this->assertInstanceOf('JaegerApp\Db', $db->where(array(
            'id' => 1
        )));
        $this->assertEquals(array(
            'id' => 1
        ), $db->getWhere());
    }

    public function testCredentials()
    {
        $db = new Db();
        $this->assertEquals(array(), $db->getCredentials());
        $this->assertInstanceOf('JaegerApp\Db', $db->setCredentials($this->getDbCreds()));
        $this->assertEquals($this->getDbCreds(), $db->getCredentials());
    }

    public function testDbObject()
    {
        $db = new Db();
        $db->setCredentials($this->getDbCreds());
        $this->assertInstanceOf('JaegerApp\Db\DbInterface', $db->getDb());
    }

    public function testGetCreateTable()
    {
        $db = new Db();
        // $sql = $this->getSettingsTableCreateSql();
        // $db->query($sql);
        $string = $db->setCredentials($this->getDbCreds())
            ->getCreateTable($this->test_table_name);
        $this->assertEquals(substr($string, 0, 29), 'CREATE TABLE `' . $this->test_table_name . '`');
        
        $string = $db->getCreateTable($this->test_table_name, true);
        $this->assertEquals(substr($string, 0, 26), 'CREATE TABLE IF NOT EXISTS');
    }

    public function testGetTableStatus()
    {
        $db = new Db();
        $table_status = $db->setCredentials($this->getDbCreds())
            ->getTableStatus($this->test_table_name);
        
        $this->assertTrue(is_array($table_status));
        $this->assertCount(1, $table_status);
        foreach ($table_status as $table) {
            $this->assertArrayHasKey('Name', $table);
            $this->assertArrayHasKey('Engine', $table);
            $this->assertArrayHasKey('Version', $table);
            $this->assertArrayHasKey('Rows', $table);
            $this->assertArrayHasKey('Row_format', $table);
            $this->assertArrayHasKey('Avg_row_length', $table);
            $this->assertArrayHasKey('Max_data_length', $table);
            $this->assertArrayHasKey('Index_length', $table);
            break;
        }
    }

    public function testGetTables()
    {
        $db = new Db();
        $tables = $db->setCredentials($this->getDbCreds())
            ->getTables();
        $this->assertTrue(is_array($tables));
        $this->assertCount(1, $tables);
        $this->assertArrayHasKey($this->test_table_name, $tables);
    }

    public function testSetDbName()
    {
        $db = new Db();
        $creds = $this->getDbCreds();
        $db->setCredentials($creds)->setDbName($creds['database_switch']);
        $tables = $db->setCredentials($this->getDbCreds())
            ->getTables();
        $this->assertTrue(is_array($tables));
        $this->assertCount(0, $tables);
        
        $db->setDbName($creds['database']);
    }

    public function testEscape()
    {
        $db = new Db();
        $string = $db->setCredentials($this->getDbCreds())
            ->escape("My String isn't really here. \"I hope this works!\"");
        $this->assertEquals($string, "My String isn\'t really here. \\\"I hope this works!\\\"");
    }

    public function testCrud()
    {
        $db = new Db();
        $db->setCredentials($this->getDbCreds())
            ->emptyTable($this->test_table_name);
        $data = $db->select()
            ->from($this->test_table_name)
            ->get();
        $this->assertTrue(is_array($data));
        $this->assertCount(0, $data);
        
        // add 2 rows and verify they're in there
        $db->insert($this->test_table_name, array(
            'setting_key' => 'test_key_value',
            'setting_value' => 'here is the value'
        ));
        $db->insert($this->test_table_name, array(
            'setting_key' => 'test_key_value2',
            'setting_value' => 'here is the value 2'
        ));
        $data = $db->select()
            ->from($this->test_table_name)
            ->get();
        $this->assertTrue(is_array($data));
        $this->assertCount(2, $data);
        
        // grab 1 row and check for such
        $data = $db->select()
            ->from($this->test_table_name)
            ->where(array(
            'id' => '1'
        ))
            ->get();
        $this->assertTrue(is_array($data));
        $this->assertCount(1, $data);
        
        // update 1 row with some data
        $db->update($this->test_table_name, array(
            'setting_value' => 'And here\'s the new content'
        ), array(
            'id' => 1
        ));
        $data = $db->select()
            ->from($this->test_table_name)
            ->where(array(
            'id' => '1'
        ))
            ->get();
        $this->assertEquals('And here\'s the new content', $data['0']['setting_value']);
        
        // remove everything and verify
        $db->setCredentials($this->getDbCreds())
            ->emptyTable($this->test_table_name);
        $data = $db->select()
            ->from($this->test_table_name)
            ->get();
        $this->assertTrue(is_array($data));
        $this->assertCount(0, $data);
    }
    
    /**
     * The Databaes Test Credentiasl
     *
     * @return array
     */
    protected function getDbCreds()
    {
        return include 'data/db.config.php';
    }    
}