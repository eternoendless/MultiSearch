<?php
/**
 * Extends PDO functionality
 * 
 * @package Foundation
 */
class PDOWrapper extends PDO {

  /**
   * Show only the query
   */
  const DEBUG_BASIC = 1;
  /**
   * Show the query and its stack trace
   */
  const DEBUG_STACK = 2;
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   *
   * @var int|false
   */
  private $debug = false;

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Creates a PDOWrapper instance representing a connection to a database
	 * @param $dsn
	 * @param $username
	 * @param $passwd
	 * @param $options [optional]
   */
  public function __construct($dsn, $username, $passwd, $options) {
    parent::__construct($dsn, $username, $passwd, $options);
    
    //$this->debug = Configuration::get('@foundation.database.debug');
  }
  
  /**
   * Sets query debugging level
   * @param int $debugLevel
   */
  public function setDebug($debugLevel) {
    $this->debug = $debugLevel;
  }
  
  /**
   * Executes an SQL statement or Query and returns a result set as a PDOStatement object
	 * @param string|Query $statement The SQL statement to prepare and execute.
	 * Data inside the query should be properly escaped.
	 * @return PDOStatement object, or FALSE
	 * on failure.
   */
  public function query($statement) {
    $params = func_get_args();
    
    if ($statement instanceof Query) {
      $statement = $statement->getSql();
      $params = array_replace($params, array(0 => $statement));
    }
    
    if ($this->debug)
      Logger::debug($this->beautifySQL($statement), null, null, ($this->debug >= self::DEBUG_STACK)? $this->debug : false);
    
    return call_user_func_array('parent::query', $params);
  }
  
  /**
   * Executes an SQL statement or Query and returns the result count
   * @param string|Query $statement
   * @return int|FALSE
   */
  public function queryCount($statement) {
    $rs = $this->query($statement);
    
    return ($rs !== false)? $rs->rowCount() : false;
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Returns a beautyfied version of an SQL statement
   * @param string $statement SQL statement
   * @return string Beautified SQL
   */
  private function beautifySQL($statement) {
    // deletes empty lines, spaces at the beginning of a line, and between parenthesis
    return preg_replace('/(?:^[\s\t]+\n?)|(?:(?<=\()\s+)|(?:\s+(?=\)))/m', '', $statement);
  }
}