<?php
/**
 * Facilitates the creation of complex conditions for a query
 * 
 * @package Foundation
 */
class QueryCondition {

  const OP_AND = 'AND';
  const OP_OR = 'OR';
  const OP_XOR = 'XOR';
  const OP_NOT = 'NOT';
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * @var string $assertion Assertion
   */
  private $assertions;
  /**
   * @var string Boolean operator (default: AND) 
   */
  private $operator;

  /**
   * @var bool $wrap TRUE to wrap the assertion with parenthesis (default: FALSE)
   */
  private $wrap;

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Returns an empty instance of the class
   * @param string $operator [optional] Boolean operator for the whole condition (default: AND)
   * @param bool $wrap [optional] TRUE to wrap the whole condition with parenthesis (default: FALSE)
   * @return \self
   */
  public static function buildEmpty($operator = self::OP_AND, $wrap = false) {
    $instance = new self('', $operator, $wrap);
    $instance->assertions = array();
    return $instance;
  }
   
  /**
   * Creates a Query Condition
   * @param string|array $assertion Assertion (use arrays for nested assertions)
   * @param string $operator [optional] Boolean operator for the whole condition (default: AND)
   * @param bool $wrap [optional] TRUE to wrap the whole condition with parenthesis (default: FALSE, autoset to TRUE if if $assertion is an array)
   * @return QueryConditon
   */
  public function __construct($assertion, $operator = self::OP_AND, $wrap = false) {
    if (!var_in($operator, self::OP_AND, self::OP_OR, self::OP_XOR, self::OP_NOT))
      throw new InvalidArgumentException(sprintf("Invalid value for boolean operator: '%s'", $operator));
    
    $this->operator = $operator;
    $this->wrap = $wrap;
    
    if (is_string($assertion))
      $this->assertions = $assertion;
    elseif ($assertion instanceof self) {
      $this->assertions = [$assertion];
    }
    elseif (!is_array($assertion))
      throw new InvalidArgumentException(sprintf("Invalid assertion type: '%s'", is_object($assertion)? get_class($assertion) : gettype($assertion)));
    else {
      $this->assertions = array();
      $this->wrap = true;
      
      if (is_string($assertion))
        $this->assertions[] = new self($assertion, $operator, $wrap);
      else {
        foreach ((array)$assertion as $k => $args) {
          if (is_null($args))
            throw new InvalidArgumentException("Invalid assertion parameter");
          else {
            // fill in missing arguments for the first item if needed
            if ($k == 0 && count($args < 3)) {
              if (!isset($args[1])) $args[1] = $operator;
              if (!isset($args[2])) $args[2] = $wrap;
            }
            $queryCondition  = new ReflectionClass($this);
            $this->assertions[] = $queryCondition->newInstanceArgs((array)$args);
          }
        }
      }
    }
    
    return $this;
  }
  
  /**
   * Adds a condition to this condition block
   * @param string|array $assertions SQL assertion(s)
   * @param string $operator [optional] Boolean operator for the first assertion (default: AND)
   * @param bool $wrap [optional] TRUE to wrap the first assertion with parenthesis (default: FALSE)
   * @throws Exception
   * @return QueryCondition
   */
  public function addCondition($assertions, $operator = self::OP_AND, $wrap = false) {
    $this->assertions = (is_array($this->assertions))? $this->assertions : [$this->assertions];
    $this->wrap = !empty($this->assertions);
    
    $this->assertions[] = new self($assertions, $operator, $wrap);
    
    return $this;
  }
  
  /**
   * Retourne TRUE si la liste de conditions est vide
   * @return bool
   */
  public function isEmpty() {
    return empty($this->assertions);
  }
  
  /**
   * Returns an SQL representation of this condition
   * @param bool $suppressOperator if TRUE, do not prepend the boolean operator before the clause
   * @return string
   */
  public function toSQLString($suppressOperator = false) {
    if (!is_array($this->assertions))
      $str = $this->assertions;
    else {
      $assertions = $this->assertions;
      
      // the first element may be a string or QueryCondition instance
      $str = array_shift($assertions);
      if ($str instanceof self)
        $str = $str->toSQLString(true);
      
      if (!empty($assertions)) {
        // all subsequent items should be QueryCondition instances
        foreach ($assertions as $item) {
          if ($item instanceof self) {
            $str .= $item->toSQLString();
          }
        }
      }
    }

    if ($this->wrap)
      $str = "($str)";
    if (!$suppressOperator)
      $str = " {$this->operator} $str";
      
    return $str;
  }
  
  /**
   * Returns an SQL string representation of this condition
   * @return string
   */
  public function __toString() {
    return $this->toSQLString(true);
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////

}