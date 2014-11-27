<?php
/**
 * Allows the creation of complex SQL queries
 * 
 * @package Foundation
 */
class Query {
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  private $distinct = false;
  private $fields = array();
  private $from = array();
  private $joins = array();
  private $where = array();
  private $groupBy = array();
  private $orderBy = array();
  private $limit = array();
  
  private $model = null;
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Creates a simple SQL UPDATE
   * @deprecated
   * @param string $table Table name
   * @param string|array $set "FIELD = VALUE" or array('field' => value, ...)
   * @param string $where SQL
   * @param bool $autoQuote [optional] if FALSE, don't quote values (null is never quoted)
   * @return string
   */
  public static function buildUpdateSQL($table, $set, $where, $autoQuote = true) {
    return SqlHelper::buildUpdate($table, $set, $where, $autoQuote);
  }
  
  /**
   * Creates an IN(...) sql statement with the contents from $valuesArray
   * @deprecated
   * @param array $valuesArray
   * @return string
   */
  public static function buildInStatement($valuesArray) {
    return SqlHelper::buildInStatement($valuesArray);
  }

  /**
   * Constructor
   * @param array $properties [optional]
   */
  public function __construct($properties = null) {
    if (!is_null($properties) && is_array($properties))
    $this->set($properties);
  }
  
  /**
   * Configures a collection of settings
   * @param array $properties Proprerties to set, following this format:
   * - fields: (string|string[]) Field list
   * - joins: (array|array[]) JOIN definition or array of JOIN definitions.
   *     Exemple: array( inner|left, tableName, array( onStatement [, onStatement] ) )
   * - where: (string|array|QueryCondition) WHERE defintions (use arrays for nested assertions)
   * - limit: (int|int[]) LIMIT definition. To specify an offset, use array(limit, offset)
   * - distinct: (bool) Appliquer DISTINCT ou pas
   * - model: (Model) Model class to construct when running the query via Query::run()
   * - groupBy: (string|string[]) GROUP BY clauses
   * - orderBy: (string|string[]|array[]) ORDER BY clauses. To specify a sort order, use array(field, ASC|DESC)
   * @param bool $append TRUE to append settings, FALSE to override settings (default: FALSE)
   * @return \Query
   */
  public function set($properties, $append = false) {
    if (is_array($properties)) {
      
      foreach ($properties as $property => $values) {
        
        if (!property_exists($this, $property))
          continue;
        
        if (!$append)
          $this->$property = array();
        
        if (is_null($values))
          continue;
          
        switch($property) {
          case 'fields':
            if (!is_array($values)) {
              $values = explode(',', $values);
              $values = array_map('trim', $values);
            }
            break;
          case 'joins':
            if (!empty($values)) {
              if (is_string($values[0])) {
                // Join statement -> array( inner|left, tableName, array( onStatement [, onStatement] ) )
                $values = array($values);
              }
              // Join statement[] -> array( joinStatement [, joinStatement] )
              foreach ($values as $join) {
                if (!empty($join)) {
                  if (count($join) < 3)
                    throw new Exception('Not enough parameters for Query::addJoin()');
                  
                  call_user_func_array(array($this,($append)? 'addJoin' : 'setJoin'), $join);
                }
              }
            }
            continue 2;
          case 'where':
            foreach(toArray($values) as $where) {
              if (!is_null($where))
                call_user_func_array(array($this,'addWhere'), (array)$where);
            }
            continue 2;
          case 'limit':
            if (!is_null($values))
              call_user_func_array(array($this,'setLimit'), (array)$values);
            continue 2;
          case 'distinct':
            $this->distinct = (bool)$values;
            continue 2;
          case 'model':
            $this->model = $values;
            continue 2;
          case 'orderBy':
            foreach((array)$values as $orderBy) {
              if (!is_null($orderBy))
                call_user_func_array(array($this,'addOrderBy'), (array)$orderBy);
            }
            continue 2;
        }

        if (!is_array($this->$property))
           $this->$property = (array)$this->$property;

        foreach ((array)$values as $v) {
          if (!is_null($v))
            $this->{$property}[] = $v;
        }
        
      }
    }
    return $this;
  }
  
  /**
   * Adds a collection of settings
   * @param array $properties Proprerties to add, following this format:
   * - fields: (string|string[]) Field list
   * - joins: (array|array[]) JOIN definition or array of JOIN definitions.
   *     Exemple: array( inner|left, tableName, array( onStatement [, onStatement] ) )
   * - where: (string|array|QueryCondition) WHERE defintions (use arrays for nested assertions)
   * - limit: (int|int[]) LIMIT definition. To specify an offset, use array(limit, offset)
   * - distinct: (bool) Appliquer DISTINCT ou pas
   * - model: (Model) Model class to construct when running the query via Query::run()
   * - groupBy: (string|string[]) GROUP BY clauses
   * - orderBy: (string|string[]|array[]) ORDER BY clauses. To specify a sort order, use array(field, ASC|DESC)
   * @return \Query
   */
  public function add($properties) {
    return $this->set($properties,true);
  }
  
  /**
   * Adds a field to the field list
   * @param string $field
   * @return \Query
   */
  public function addField($field) {
    $this->fields[] = $field;
    return $this;
  }
  
  /**
   * Sets the fields to select
   * @param array|string $fields
   */
  public function setFields($fields) {
    $this->fields = (array)$fields;
  }
  
  /**
   * Adds a table to the FROM clause
   * @param string $from Table name
   * @return \Query
   */
  public function addFrom($from) {
    $this->from[] = $from;
    return $this;
  }
  
  /**
   * Adds a join to the query
   * @param string $type INNER, LEFT, RIGHT, OUTER, etc.
   * @param string $table Table name
   * @param string|array $on Join condition
   * @param bool $overrideExisting If set to TRUE, overrides any existing join to the same table; otherwise, settings are appended [default: FALSE]
   * @return \Query
   */
  public function addJoin($type, $table, $on, $overrideExisting = false) {
    $join = $this->createJoin($type, $table, $on);
    $idx = $this->hasJoin($table);
    if ($idx === false)
      $this->joins[] = $join;
    elseif ($overrideExisting)
      $this->overrideJoin($idx, $on);
    else
      $this->mergeJoin($idx, $on);
    return $this;
  }
  
  /**
   * Adds a join to the query while overriding any previous join to the same table
   * @param string $type INNER, LEFT, RIGHT, OUTER, etc.
   * @param string $table Table name
   * @param string|array $on Join condition
   */
  public function setJoin($type, $table, $on) {
    $this->addJoin($type, $table, $on, true);
  }
  
  /**
   * Removes a join
   * @param string $table Table name
   * @return bool TRUE if the join was removed, FALSE otherwise
   */
  public function removeJoin($table) {
    $idx = $this->hasJoin($table);
    if ($idx !== false) {
      unset($this->joins[$idx]);
      return true;
    }
    return false;
  }
  
  /**
   * Changes the join type for a specific table
   * @param string $table Table name
   * @param string $newType New join type (LEFT, RIGHT, INNER, etc)
   * @return bool TRUE if the join was modified, FALSE otherwise
   */
  public function changeJoinType($table, $newType) {
    $idx = $this->hasJoin($table);
    if ($idx !== false) {
      $this->joins[$idx][0] = $newType;
      return true;
    }
    return false;
  }
  
  /**
   * Adds a condition to a join
   * @param string $table Table name
   * @param string|array $on Join condition
   * @return bool TRUE if successfully added, FALSE otherwise
   */
  public function addJoinCondition($table, $on) {
    $idx = $this->hasJoin($table);
    if ($idx !== false) {
      $this->mergeJoin($idx, $on);
      return true;
    }
    return false;
  }
  
  /**
   * Sets (overrides) the conditions for a join
   * @param string $table Table name
   * @param string|array $on Join condition
   * @return boolean TRUE if successfully set, FALSE otherwise
   */
  public function setJoinCondition($table, $on) {
    $idx = $this->hasJoin($table);
    if ($idx !== false) {
      $this->overrideJoin($idx, $on);
      return true;
    }
    return false;
  }
  
  /**
   * Finds out if this Query contains a join to $table.
   * Returns the join index if found, or FALSE otherwise
   * @param string $table Table name
   * @return int|FALSE
   */
  public function hasJoin($table) {
    if (count($this->joins)> 0) {
      foreach ($this->joins as $key => $item) {
        if ($item['table'] == $table)
          return $key;
      }
    }
    
    return false;
  }
  
  /**
   * Adds a join clause at the first position in the join list
   * @param string $type INNER, LEFT, RIGHT, OUTER, etc.
   * @param string $table Table name
   * @param string|array $on Join condition
   * @return \Query
   */
  public function prependJoin($type, $table, $on) {
    array_unshift($this->joins, array(
      'type'  => $type,
      'table' => $table,
      'on'    => $on
    ));
    return $this;
  }
  
  /**
   * Adds a condition to the Query at the first position in the list
   * @param string|array|QueryCondition $assertion Assertion (use arrays for nested assertions)
   * @param string $operator [optional] Boolean operator (default: AND)
   * @param bool $wrap [optional] TRUE to wrap the assertion with parenthesis (default: FALSE, autoset to TRUE if $assertion is an array)
   * @return \Query
   */
  public function prependWhere($assertion, $operator = QueryCondition::OP_AND, $wrap = false) {
     $where = ($assertion instanceof QueryCondition)?
      $assertion : new QueryCondition($assertion, $operator, $wrap);
    
    if (!in_array($where, $this->where)) {
      array_unshift($this->where, $where);
    }
    return $this;
  }
  
  /**
   * Adds a condition to the Query
   * @param string|array|QueryCondition $assertion Assertion (use arrays for nested assertions)
   * @param string $operator [optional] Boolean operator (default: AND)
   * @param bool $wrap [optional] TRUE to wrap the assertion with parenthesis (default: FALSE, autoset to TRUE if $assertion is an array)
   * @return \Query
   */
  public function addWhere($assertion, $operator = QueryCondition::OP_AND, $wrap = false) {
    $where = ($assertion instanceof QueryCondition)?
      $assertion : new QueryCondition($assertion, $operator, $wrap);
    
    if (!in_array($where, $this->where))
      $this->where[] = $where;
    return $this;
  }
  
  /**
   * Appends an assertion to an existing query condition
   * @param int $position Condition index within the query
   * @param string|array $assertion Assertion (use arrays for nested assertions)
   * @param string $operator [optional] Boolean operator (default: AND)
   * @param bool $wrap [optional] TRUE to wrap the assertion with parenthesis (default: FALSE, autoset to TRUE if $assertion is an array)
   */
  public function appendWhere($position, $assertion, $operator = QueryCondition::OP_AND, $wrap = false) {
    if (!isset($this->where[$position]))
      throw new OutOfBoundsException(sprintf("Index out of bounds: %d for appendWhere", $position));
    
    $this->where[$position]->addCondition($assertion, $operator, $wrap);
  }
  
  /**
   * Adds a GROUP BY clause
   * @param string $fieldName
   * @return \Query
   */
  public function addGroupBy($fieldName) {
    $this->groupBy[] = $fieldName;
    return $this;
  }
  
  /**
   * Adds an ORDER BY clause
   * @param string $fieldName Fieldname to order by
   * @param string $direction Order direction ('ASC','DESC')
   * @return \Query
   */
  public function addOrderBy($fieldName, $direction = null) {
    $this->orderBy[] = array(
      'field'     => $fieldName,
      'direction' => $direction
    );
    return $this;
  }
  
  /**
   * Sets the LIMIT using the passed paramenters
   * @param int $limit Maximum number of results
   * @param int $offset [optional] Result set offset point
   * @return \Query
   */
  public function setLimit($limit, $offset = null) {
    $this->limit = array(
      'offset' => $offset,
      'limit' => $limit
    );
    return $this;
  }
  
  /**
   * Sets whether to select distinct or not
   * @param bool $toggle
   * @return \Query
   */
  public function setDistinct($toggle) {
    $this->distinct = (bool)$toggle;
    return $this;
  }
  
  /**
   * Returns an SQL representation of the Query
   * @return string
   */
  public function getSql() {
    return $this->buildToSql();
  }
  
  /**
   * @alias getSql()
   * @return string
   */
  public function toSql() {
    return $this->getSql();
  }
  
  /**
   * Runs the query in the current model context.
   * Note: You *need* to have set a model for this to work
   * @return ModelCollection|null
   * @throws Exception
   */
  public function run() {
    if (!isset($this->model) || !class_exists($this->model))
      throw new Exception("Can't run a Query object without a model context");
    
    return call_user_func("{$this->model}::findByQuery",$this);
  }
  
  /**
   * Returns the query count within the current model context.
   * Note: You *need* to have set a model for this to work
   * @return int
   * @throws Exception
   */
  public function runCount() {
    if (!isset($this->model) || !class_exists($this->model))
      throw new Exception("Can't run a Query object without a model context");
    
    return call_user_func("{$this->model}::findCountByQuery",$this);
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Creates a condition object using the passed parameters
   * @param string $assertion
   * @param string $operator [optional] AND, OR, XOR
   * @param bool $wrap [optional] TRUE to wrap the assertion with parenthesis (default: FALSE)
   * @return array
   */
  private function createWhere($assertion, $operator = QueryCondition::OP_AND, $wrap = false) {
    return new QueryCondition($assertion, $operator, $wrap);
  }
  
  /**
   * Creates a join pseudo-object from using passed parameters
   * @param string $type INNER, LEFT, RIGHT, OUTER, etc...
   * @param string $table Table name
   * @param string|array $on Join condition
   * @return array
   */
  private function createJoin($type, $table, $on) {
    $onStatements = array();
    foreach ((array)$on as $onItem) {
      if (!is_null($onItem))
        $onStatements[] = call_user_func_array(array($this,'createWhere'),(array)$onItem);
    }
    return array(
      'type'  => $type,
      'table' => $table,
      'on'    => $onStatements
    );
  }
  
  /**
   * Merges $on into the join conditions for the join at the $idx position in the joins array
   * @param int $idx Join position in the joins array
   * @param mixed $on Join condition
   */
  private function mergeJoin($idx, $on) {
    $this->overrideJoin($idx, $on, true);
  }
  
  /**
   * Overrides or merges the join conditions with $on for the join at the $idx position
   * @param int $idx Join position in the joins array
   * @param mixed $on Join condition
   * @param bool $merge If TRUE, merge provided join conditions with current;
   * if FALSE, replace all conditions for the ON statement [default: FALSE]
   */
  private function overrideJoin($idx, $on, $merge = false) {
    $joinOn =& toArray($this->joins[$idx]['on']);
    
    if (!$merge)
      $joinOn = array();
    
    foreach ((array)$on as $rawStatement) {
      if (!is_null($rawStatement)) {
        $statement = call_user_func_array(array($this,'createWhere'), (array)$rawStatement);
        if (!$merge || !in_array($statement, $joinOn))
          $joinOn[] = $statement;
      }
    }
  }
  
  /**
   * Creates a WHERE/ON clause from the settings passed
   * @param array|string $where
   * @param bool $linebreak [optional] TRUE to prepend a line break to each statement
   * @return string
   */
  private function buildWhereClauses($where, $linebreak = true) {
    if (!is_array($where)) {
      // where = 'blah = bleh'
      return $where;
    }
    
    $clauses = array();
    /* @var $whereItem QueryCondition  */
    foreach ($where as $whereItem) {
      if (!is_object($whereItem)) {
        // where = array('blah = bleh', object(...), 'AND bleh = blah')
        $clauses[] = $whereItem;
      }
      else {
        $clauses[] = $whereItem->toSQLString(empty($clauses));
      }
    }
    return implode($linebreak? "\n" : ' ',$clauses);
  }
  
  /**
   * Creates an ORDER BY clause from the provided settings
   * @param array $orderBy
   * @return string
   */
  private function buildOrderByClauses($orderBy) {
    if (!is_array($orderBy))
      return $orderBy;
    
    $clauses = array();
    foreach ($this->orderBy as $obClause) {
      if (!is_array($obClause)) {
        $clauses[] = $obClause;
      }
      else {
        $current = $obClause['field'];
        if (!is_null($obClause['direction']))
          $current .= ' '.$obClause['direction'];
        $clauses[] = $current;
      }
    }
    return implode(', ',$clauses);
  }
  
  /**
   * Converts the query to SQL
   * @return string
   */
  private function buildToSql() {
    $sql = array();
    $sql[] = "SELECT "
      . (($this->distinct)? 'DISTINCT ' : '')
      . implode(', ', $this->fields);
    $sql[] = "FROM ".implode(', ', $this->from);
    if (!empty($this->joins)) {
      foreach ($this->joins as $join) {
        if (!is_array($join))
          $sql[] = $join;
        else {
          $on = $this->buildWhereClauses($join['on'],false);
          
          $sql[] = "{$join['type']} JOIN {$join['table']} ON $on";
        }
      }
    }
    if (!empty($this->where)) {
      $sql[] = "WHERE ".$this->buildWhereClauses($this->where);
    }
    if (!empty($this->groupBy)) {
      $sql[] = 'GROUP BY '.implode(', ',$this->groupBy);
    }
    if (!empty($this->orderBy)) {
      $sql[] = 'ORDER BY '.$this->buildOrderByClauses($this->orderBy);
    }
    if (!empty($this->limit)) {
      $limit = "LIMIT ";
      if (!is_array($this->limit)) {
        $limit .= $this->limit;
      }
      else {
        if (!is_null($this->limit['offset']))
          $limit .= "{$this->limit['offset']}, ";
          
        $limit .= $this->limit['limit'];
      }
      $sql[] = $limit;
    }
    return implode("\n",$sql);
  }

}