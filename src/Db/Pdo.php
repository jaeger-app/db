<?php
/**
 * mithra62
 *
 * @copyright	Copyright (c) 2015, mithra62, Eric Lamb.
 * @link		http://mithra62.com/
 * @version		1.0
 * @filesource 	./mithra62/Db/Pdo.php
 */
 
namespace JaegerApp\Db;

use Aura\Sql\ExtendedPdo; 
use Aura\SqlQuery\QueryFactory;

/**
 * mithra62 - PDO Database Object
 *
 * Wrapper for a simple PDO abstraction
 *
 * @package Database
 * @author Eric Lamb <eric@mithra62.com>
 */
class Pdo implements DbInterface
{
    /**
     * The primary table we're working with
     * @var string
     */
    protected $table = null;
    
    /**
     * Any filtering for a WHERE SQL clause
     * @var mixed
     */
    protected $where = false;
    
    /**
     * The database connection credentials
     * @var array
     */
    protected $credentials = array();
    
    /**
     * The database object we're piggybacking on
     * @var \Aura\Sql\ExtendedPdo
     */
    protected $db = null;
    
    /**
     * A matched array of "bad" characters our string manipulation hates
     * @var array
     */
    protected $escape_chars = array(
        'search' => array("\\", "\0", "\n", "\r", "\x1a", "'", '"'),
        'replace' => array("\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"')
    );
    
    /**
     * (non-PHPdoc)
     * @see \mithra62\Db\DbInterface::select()
     */
    public function select($table, $where)
    {
        $this->table = $table;
        $this->where = $where;
        return $this;
    }
    
    /**
     * (non-PHPdoc)
     * @see \mithra62\Db\DbInterface::insert()
     */
    public function insert($table, array $data = array())
    {
        $query_factory = new QueryFactory('mysql');
        $insert = $query_factory->newInsert();
        $insert->into($table);
        $cols = $bind = array();
        foreach($data AS $key => $value)
        {
            $cols[] = $key;
            $bind[$key] = $value;
        }
        
        $insert->cols($cols)->bindValues($bind);
        $sth = $this->getDb()->prepare($insert->getStatement());
        $sth->execute($insert->getBindValues());
        
        $name = $insert->getLastInsertIdName('id');
        return $this->getDb()->lastInsertId($name);
    }
    
    /**
     * (non-PHPdoc)
     * @see \mithra62\Db\DbInterface::update()
     */
    public function update($table, $data, $where)
    {
        $query_factory = new QueryFactory('mysql');
        $update = $query_factory->newUpdate()->table($table);
        foreach($data AS $key => $value)
        {
            $cols[] = $key;
            $bind[$key] = $value;
        }
        
        if (is_string($where)) {
            $where = $this->escape($where, false, false);
        } elseif (is_array($where)) {
            $where = $this->parseArrayPair($where, 'AND');
        } else {
            $where = '';
        }        
        
        $update->cols($cols)->where($where)->bindValues($bind);
        $sth = $this->getDb()->prepare($update->getStatement());
        return $sth->execute($update->getBindValues());
    }
    
    /**
     * (non-PHPdoc)
     * @see \Aura\Sql\ExtendedPdo::query()
     */
    public function query($sql = '', $params = false)
    {
        if( strtolower(substr($sql,0, 6)) == 'select' || strtolower(substr($sql,0, 4)) == 'show' ){
            return $this->getDb()->fetchAll($sql);
        } else {
            return $this->getDb()->exec($sql);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see \mithra62\Db\DbInterface::escape()
     */
    public function escape($string)
    {
        return str_replace($this->escape_chars['search'], $this->escape_chars['replace'], $string);        
        //return ltrim(rtrim($this->getDb()->quote($string), "'"), "'");
    }
    
    /**
     * (non-PHPdoc)
     * @see \mithra62\Db\DbInterface::getAllTables()
     */
    public function getAllTables()
    {
        $sql = 'SHOW TABLES';
        return $this->getDb()->fetchAll($sql);
    }
    
    /**
     * (non-PHPdoc)
     * @see \mithra62\Db\DbInterface::getTableStatus()
     */
    public function getTableStatus()
    {
        $sql = 'SHOW TABLE STATUS';
        return $this->getDb()->fetchAll($sql);  
    }
    
    /**
     * (non-PHPdoc)
     * @see \mithra62\Db\DbInterface::getCreateTable()
     */
    public function getCreateTable($table, $if_not_exists = false)
    {
        $sql = sprintf('SHOW CREATE TABLE `%s` ;', $table);
        $statement = $this->query($sql, true);
        $string = false;
        if (! empty($statement['0']['Create Table'])) {
            $string = $statement['0']['Create Table'];
        }
        
        if ($if_not_exists) {
            $replace = substr($string, 0, 12);
            if ($replace == 'CREATE TABLE') {
                $string = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS ', $string);
            }
        }
        
        return $string;
    }
    
    /**
     * (non-PHPdoc)
     * @see \mithra62\Db\DbInterface::clear()
     */
    public function clear()
    {
        $this->table = null;
        $this->where = null;
        return $this;
    }
    
    /**
     * (non-PHPdoc)
     * @see \mithra62\Db\DbInterface::totalRows()
     */
    public function totalRows($table)
    {
        $sql = sprintf('SELECT COUNT(*) AS count FROM `%s`', $table);
        $statement = $this->query($sql, true);
        if ($statement) {
            if (isset($statement['0']['count'])) {
                return $statement['0']['count'];
            }
        }
        
        return '0';
    }
    
    /**
     * (non-PHPdoc)
     * @see \mithra62\Db\DbInterface::getColumns()
     */
    public function getColumns($table)
    {
        $sql = sprintf('SHOW COLUMNS FROM `%s`', $table);
        $statement = $this->query($sql, true);
        if ($statement) {
            return $statement;
        }
        return array();
    }
    
    /**
     * (non-PHPdoc)
     * @see \mithra62\Db\DbInterface::get()
     */
    public function get()
    {
        $query_factory = new QueryFactory('mysql');
        $select = $query_factory->newSelect();
        $select->cols(array('*'))->from($this->table);
        
        if (is_string($this->where)) {
            $where = $this->escape($this->where);
        } elseif (is_array($this->where)) {
            $where = $this->parseArrayPair($this->where, 'AND');
        } else {
            $where = '';
        }
        
        $select->where($where);
        
        $sql = $select->getStatement();
        
        $return = $this->getDb()->fetchAll($sql);
        if($return)
        {
            return $return;
        }
        return array();    
    }
    
    /**
     * 
     * @param array $credentials
     * @return \mithra62\Db\Mysqli
     */
    public function setCredentials(array $credentials)
    {
        $this->credentials = $credentials;
        return $this;
    }
    
    /**
     * (non-PHPdoc)
     * @see \mithra62\Db\DbInterface::getDb()
     */
    public function getDb($force = false)
    {
        if (is_null($this->db) || $force) {
        
            $this->db = new ExtendedPdo(
                'mysql:host='.$this->credentials['host'].';dbname='.$this->credentials['database'],
                $this->credentials['user'],
                $this->credentials['password'],
                array(), // driver options as key-value pairs
                array()  // attributes as key-value pairs
            );
            
            $this->db->setAttribute(ExtendedPdo::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }
        
        return $this->db;
    }
    
    /**
     * 
     * @param unknown $db_name
     */
    public function setDbName($db_name)
    {
        $this->credentials['database'] = $db_name;
        $this->getDb(true);
        return $this;
    }
    
    /**
     * Takes the WHERE array clause and prepairs it for use
     * @param array $arrayPair
     * @param string $glue
     */
    protected function parseArrayPair($arrayPair, $glue = ',')
    {
        // init
        $sql = '';
        $pairs = array();
    
        if (!empty($arrayPair)) {
    
            foreach ($arrayPair as $_key => $_value) {
                $_connector = '=';
                $_key_upper = strtoupper($_key);
    
                if (strpos($_key_upper, ' NOT') !== false) {
                    $_connector = 'NOT';
                }
    
                if (strpos($_key_upper, ' IS') !== false) {
                    $_connector = 'IS';
                }
    
                if (strpos($_key_upper, ' IS NOT') !== false) {
                    $_connector = 'IS NOT';
                }
    
                if (strpos($_key_upper, ' IN') !== false) {
                    $_connector = 'IN';
                }
    
                if (strpos($_key_upper, ' NOT IN') !== false) {
                    $_connector = 'NOT IN';
                }
    
                if (strpos($_key_upper, ' BETWEEN') !== false) {
                    $_connector = 'BETWEEN';
                }
    
                if (strpos($_key_upper, ' NOT BETWEEN') !== false) {
                    $_connector = 'NOT BETWEEN';
                }
    
                if (strpos($_key_upper, ' LIKE') !== false) {
                    $_connector = 'LIKE';
                }
    
                if (strpos($_key_upper, ' NOT LIKE') !== false) {
                    $_connector = 'NOT LIKE';
                }
    
                if (strpos($_key_upper, ' >') !== false && strpos($_key_upper, ' =') === false) {
                    $_connector = '>';
                }
    
                if (strpos($_key_upper, ' <') !== false && strpos($_key_upper, ' =') === false) {
                    $_connector = '<';
                }
    
                if (strpos($_key_upper, ' >=') !== false) {
                    $_connector = '>=';
                }
    
                if (strpos($_key_upper, ' <=') !== false) {
                    $_connector = '<=';
                }
    
                if (strpos($_key_upper, ' <>') !== false) {
                    $_connector = '<>';
                }
    
                if (
                    is_array($_value)
                    &&
                    (
                        $_connector == 'NOT IN'
                        ||
                        $_connector == 'IN'
                    )
                ) {
                    foreach ($_value as $oldKey => $oldValue) {
                        /** @noinspection AlterInForeachInspection */
                        $_value[$oldKey] = $this->escape($oldValue);
                    }
                    $_value = '(' . implode(',', $_value) . ')';
                } elseif (
                    is_array($_value)
                    &&
                    (
                        $_connector == 'NOT BETWEEN'
                        ||
                        $_connector == 'BETWEEN'
                    )
                ) {
                    foreach ($_value as $oldKey => $oldValue) {
                        /** @noinspection AlterInForeachInspection */
                        $_value[$oldKey] = $this->secure($oldValue);
                    }
                    $_value = '(' . implode(' AND ', $_value) . ')';
                } else {
                    $_value = $this->getDb()->quote($_value);
                }
    
                $quoteString = '`'.$_key.'`';//$this->escape(trim(str_ireplace($_connector, '', $_key)));
                $pairs[] = ' ' . $quoteString . ' ' . $_connector . ' ' . $_value . " \n";
            }
    
            $sql = implode($glue, $pairs);
        }
    
        return $sql;
    }    
}