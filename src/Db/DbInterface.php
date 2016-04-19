<?php
/**
 * Jaeger
 *
 * @copyright	Copyright (c) 2015-2016, mithra62
 * @link		http://jaeger-app.com
 * @version		1.0
 * @filesource 	./Db/DbInterface.php
 */
 
namespace JaegerApp\Db;

/**
 * Jaeger - Database Object Interface
 *
 * Outlines the methods the database drivers must adhere to
 *
 * @package Database
 * @author Eric Lamb <eric@mithra62.com>
 */
interface DbInterface {   
    
    /**
     * Sets the table and where clauses
     * @param string $table The name of the primary table we're querying
     * @param mixed $where Either a string or array of key => value for the WHERE clause
     */
    public function select($table, $where);
    
    /**
     * Cleans a string for use in a query
     * @param string $string
     * @return string
     */
    public function escape($string);
    
    /**
     * Returns an array of tables
     * @return array
     */
    public function getAllTables();
    
    /**
     * Inserts a row into a table
     * @param string $table
     * @param array $data
     * @return false|int|string false on error
     */
    public function insert($table, array $data = array());
    
    /**
     * Updates a database row
     * @param string $table
     * @param array $data
     * @param mixed $where
     */
    public function update($table, $data, $where);
    
    /**
     * Executes a SQL query
     * @param string $sql
     * @param bool $return
     */
    public function query($sql = '', $return = false);
    
    /**
     * Returns the table status query
     * @return array
     */
    public function getTableStatus();
    
    /**
     * Returns the CREATE TABLE statement for the given $table
     *
     * @param string $table
     *            The name of the table to create a statement for
     * @param bool $if_not_exists
     *            If set to true, the statement will append IF NOT EXISTS
     * @return Ambigous <boolean, mixed>
     */
    public function getCreateTable($table, $if_not_exists = false);
    
    /**
     * Clears up any properties back to defaults
     * @return void
     */
    public function clear();
    
    /**
     * Returns how many rows are in the given table
     * @param string $table
     */
    public function totalRows($table);
    
    /**
     * Returns the columns for a given table
     * @param string $table
     * @return array
     */
    public function getColumns($table);
    
    /**
     * Executes the setup SELECT query and returns the result as an array
     * @return array
     */
    public function get();
    
    /**
     * Returns the database object we're using
     * @param bool $force Whether to get a new instance of the db object
     * @return mixed
     */
    public function getDb($force = false);
    
}