<?php
/**
 * Jaeger
 *
 * @copyright	Copyright (c) 2015-2016, mithra62
 * @link		http://jaeger-app.com
 * @version		1.0
 * @filesource 	./Db.php
 */
namespace JaegerApp;

use JaegerApp\Exceptions\DbException;

/**
 * Jaeger - Database Object
 *
 * Wrapper for a simple database interface
 *
 * @package Database
 * @author Eric Lamb <eric@mithra62.com>
 */
class Db
{
    /**
     * The external db object
     * 
     * @var Db\DbInterface
     */
    protected $db = null;

    /**
     * The database connection details
     * 
     * @var array
     */
    protected $credentials = array();

    /**
     * The columns we want
     * 
     * @var array
     */
    protected $columns = array();

    /**
     * The WHERE for the SQL
     * 
     * @var mixed<array/string>
     */
    protected $where = '1=1';

    /**
     * The table name we're working with
     * 
     * @var string
     */
    protected $table = false;
    
    /**
     * How we want to access the database engine
     * mysqli is the default
     * @var string
     */
    protected $access_type = 'mysqli';
    
    /**
     * Sets the method of accessing the database
     * @param string $type
     * @return JaegerApp\Db
     */
    public function setAccessType($type)
    {
        $this->access_type = $type;
        return $this;
    }
    
    /**
     * Returns the access type for how we're interacting with the database
     * @return string
     */
    public function getAccessType()
    {
        return $this->access_type;
    }

    /**
     * Set the columns we want to return
     * 
     * @param array $columns            
     * @return \JaegerApp\Db
     */
    public function select(array $columns = array())
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Sets the main table we're pulling from
     * 
     * @param string $table            
     * @return \JaegerApp\Db
     */
    public function from($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Sets the SQL WHERE
     *
     * Can be either a string or an array of key=>value pairs
     * 
     * @param array $where            
     * @return \JaegerApp\Db
     */
    public function where($where = array())
    {
        $this->where = $where;
        return $this;
    }

    /**
     * Returns the SQL WHERE
     * 
     * @return \JaegerApp\mixed<array/string>
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * Returns the table
     * 
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Set the credentials for accessing the database
     * 
     * @param array $credentials            
     * @return \JaegerApp\Db
     */
    public function setCredentials(array $credentials)
    {
        $this->credentials = $credentials;
        return $this;
    }

    /**
     * Returns the credentials
     * 
     * @return \JaegerApp\array
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * Executes and performs a query
     * 
     * @return array
     */
    public function get()
    {
        return $this->getDb()->select($this->getTable(), $this->getWhere())->get();
    }

    /**
     * Inserts data into the $table
     * 
     * @param string $table            
     * @param data $data            
     * @return bool
     */
    public function insert($table, $data = array())
    {
        return $this->getDb()->insert($table, $data);
    }

    /**
     * Updates a table
     * 
     * @param string $table            
     * @param array $data            
     * @param string $where            
     */
    public function update($table, $data = array(), $where = '1=1')
    {
        if (! $this->getDb()->update($table, $data, $where)) {
            throw new DbException("Failed updating " . $table);
        }
        
        return true;
    }

    /**
     * Truncates a given table
     * 
     * @param string $table            
     */
    public function emptyTable($table)
    {
        return $this->getDb()->query("TRUNCATE " . $table);
    }

    /**
     * Executes a query and, optionally, returns the result
     * 
     * @param string $sql            
     * @param string $return            
     * @return multitype:
     */
    public function query($sql, $return = false)
    {
        $query = $this->getDb()->query($sql, true);
        if ($return) {
            return $query;
        }
    }

    /**
     * Returns an instance of the database object
     * 
     * @todo Update engine selection to be 
     *          polymorphic instead of conditional
     * @return Db\DbInterface
     * @throws Exceptions\DbException
     */
    public function getDb()
    {
        if (is_null($this->db)) {
            $creds = $this->getCredentials();
            
            //try explicit type setting
            if( $this->getAccessType() == 'mysqli' && function_exists('mysqli_select_db') ){
                $this->db = new Db\Mysqli();
            }
            else 
            {
                if ( $this->getAccessType() == 'pdo' && class_exists('PDO') ){
                    $this->db = new Db\Pdo();
                }
            }
            
            //fuck it; we're just gonna choose one then
            if(is_null($this->db)){
                if( class_exists('PDO') ){
                    $this->db = new Db\Pdo(); 
                }
                elseif ( function_exists('mysqli_select_db') ) {
                    $this->db = new Db\Mysqli();
                }
                else {
                    throw new DbException('Database engine not available! Must be either PDO or mysqli');
                }
            }
            
            $this->db->setCredentials($this->credentials);
        }
        
        return $this->db;
    }

    /**
     * Returns all the available tables
     * 
     * @return array
     */
    public function getTables()
    {
        $tables = $this->getDb()->getAllTables();
        $return = array();
        foreach ($tables as $name => $table) {
            foreach ($table as $key => $value) {
                $return[$table[$key]] = $table[$key];
            }
        }
        
        return $return;
    }

    /**
     * Returns the details for all the tables
     * 
     * @return array
     */
    public function getTableStatus()
    {
        $tables = $this->getDb()->getTableStatus();
        //query("SHOW TABLE STATUS", true);
        return $tables;
    }

    /**
     * Returns the CREATE TABLE statement for the given $table
     * 
     * @param string $table
     *            The name of the table to create a statement for
     * @param bool $if_not_exists
     *            If set to true, the statement will append IF NOT EXISTS
     * @return Ambigous <boolean, mixed>
     */
    public function getCreateTable($table, $if_not_exists = false)
    {        
        return $this->getDb()->getCreateTable($table, $if_not_exists);
    }

    /**
     * Escapes a string for db use
     * 
     * @param string $string            
     * @return Ambigous <\voku\db\array, \voku\db\bool, \voku\db\float, \voku\db\int, \voku\db\string>
     */
    public function escape($string)
    {
        return $this->getDb()->escape($string);
    }

    /**
     * Changes the database to $db_name
     * 
     * @param string $db_name
     *            The name of the database we want to change to
     * @return \JaegerApp\Db
     */
    public function setDbName($db_name)
    {
        $this->getDb()->setDbName($db_name);
        return $this;
    }
    
    /**
     * Clears any records in memory associated with a result set.
     * It allows calling with no select query having occurred.
     *
     * @param resource $result
     * @access public
     */
    public function clear()
    {
        $this->getDb()->clear();
    }
    
    /**
     * Returns the total number of rows in a given table without filtering
     * @param string $table The name of the table
     * @return number
     */
    public function totalRows($table)
    {
        return $this->getDb()->totalRows($table);
    }
    
    /**
     * Returns the columns on a given table
     * @param string $table
     * @return array
     */
    public function getColumns($table)
    {
        return $this->getDb()->getColumns($table);
    }
    
    /**
     * Determines whether a given database exists
     * @param string $name
     * @return boolean
     */
    public function checkDbExists($name)
    {
        $data = $this->query("SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '".$this->escape($name)."'", true);
        if( isset($data['0']['total']) && $data['0']['total'] == '1' )
        {
            return true;    
        }
        
        return false;
    }
    
}