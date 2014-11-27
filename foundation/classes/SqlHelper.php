<?php

/**
 * Helps creating SQL statements
 */
class SqlHelper {

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Creates a simple SQL UPDATE
   * @param string $table Table name
   * @param string|array $data "FIELD = VALUE" or array('field' => value, ...)
   * @param string $where SQL where
   * @param bool $autoQuote [optional] if FALSE, don't quote values (null is never quoted)
   * @return string SQL string
   */
  public static function buildUpdate($table, $data, $where, $autoQuote = true) {
    $setSql = self::buildSet($data, $autoQuote);
    return "UPDATE $table 
      $setSql
      WHERE $where";
  }
  
  /**
   * Creates a simple SQL DELETE
   * @param string $table Table Name
   * @param string $where SQL where
   * @param int $limit [optional] if greater than 0, delete limit (default: 0)
   * @return string SQL string
   */
  public static function buildDelete($table, $where, $limit = 0) {
    $sql = "DELETE FROM $table WHERE $where";
    if ($limit > 0)
      $sql .= "LIMIT ".(int)$limit;
    
    return $sql;
  }
  
  /**
   * Creates a simple SQL INSERT
   * @param string $table Table Name
   * @param string|array $data "FIELD = VALUE" or array('field' => value, ...)
   * @param bool $ignore [optional] if TRUE, create an INSERT IGNORE instead of a normal INSERT (default: FALSE)
   * @param bool $autoQuote [optional] if FALSE, don't quote values (null is never quoted)
   * @return string SQL string
   */
  public static function buildInsert($table, $data, $ignore = false, $autoQuote = true) {
    $setSql = self::buildSet($data, $autoQuote);
    return "INSERT ".(($ignore)? 'IGNORE ' : ' ')."INTO $table 
      $setSql";
  }
  
  /**
   * Creates an ON DUPLICATE KEY UPDATE (field = value, ...) statement
   * @param string|array $data "FIELD = VALUE" or array('field' => value, ...)
   * @param bool $autoQuote [optional] if FALSE, don't quote values (null is never quoted)
   * @return string SQL string
   */
  public static function buildOnDuplicateUpdate($data, $autoQuote = true) {
    return self::buildSetStatement($data, $autoQuote, 'ON DUPLICATE KEY UPDATE');
  }
  
  /**
   * Creates an SQL SET (field = value, ...) statement
   * @param string|array $data "FIELD = VALUE" or array('field' => value, ...)
   * @param bool $autoQuote [optional] if FALSE, don't quote values (null is never quoted)
   * @return string SQL string
   */
  public static function buildSet($data, $autoQuote = true) {
    return self::buildSetStatement($data, $autoQuote);
  }

  /**
   * Creates an IN(...) sql statement with the contents from $valuesArray
   * @param array $valuesArray
   * @return string
   */
  public static function buildInStatement($valuesArray) {
    $db = Database::get();
    
    return ' IN('.implode(',',array_map(array($db,'quote'),(array)$valuesArray)).')';
  }


  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Creates an SQL SET (field = value, ...) statement
   * @param string|array $data "FIELD = VALUE" or array('field' => value, ...)
   * @param bool $autoQuote [optional] if FALSE, don't quote values (null is never quoted)
   * @param string $setText [optional] Replacement for SET text (example: 'ON DUPLICATE KEY UPDATE'). Default: 'SET'
   * @return string SQL string
   */
  private static function buildSetStatement($data, $autoQuote = true, $setText = 'SET') {
    if ($autoQuote)
      $db = Database::get();
    
    if (is_object($data))
      $data = (array)($data);
    
    if (!is_array($data))
      $setParams = (string)$data;
    else {
      foreach ($data as $field => $value) {
        if ($value === null)
          $val = 'NULL';
        else
          $val = $autoQuote? $db->quote($value) : $value;
          
        $setParams[] = "$field = $val";
      }
      $setParams = implode(",\n",$setParams);
    }
    
    return "$setText
      $setParams";
  }

}