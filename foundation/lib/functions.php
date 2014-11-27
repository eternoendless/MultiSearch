<?php

/**
 * Returns TRUE if $var1, $var2... $var[N] are ALL empty
 * @param mixed $var1
 * @param mixed $var2
 * @param mixed $varN
 * @return bool
 */
function are_empty($var1, $var2) {
  $args = func_get_args();
  foreach ($args as $val) {
    if (!empty($val))
      return false;
  }
  return true;
}

/**
 * Returns TRUE if $var1, $var2... $var[N] are ALL null
 * @param mixed $var1
 * @param mixed $var2
 * @param mixed $varN
 * @return bool
 */
function are_null($var1, $var2) {
  $args = func_get_args();
  foreach ($args as $val) {
    if (!is_null($val))
      return false;
  }
  return true;
}

/**
 * Returns TRUE if $var equals $value1, or $value2, or $value[N]
 * @param mixed $var
 * @param mixed $value1
 * @param mixed $value2... [optional]
 * @return bool TRUE if found, FALSE otherwise
 */
function var_in($test, $value1) {
  $args = func_get_args();
  array_shift($args);
  if (!is_null($test))
    return in_array($test, $args);
  else {
    foreach ($args as $val) {
      if ($test === $val) return true;
    }
    return false;
  }
}

/**
 * Returns TRUE if $key1, $key2... $key[N] exist within the array.
 * If an array is provided as $key, every subsequent parameter is ignored
 * @param array $array
 * @param mixed $key1
 * @param mixed $key2
 * @return type
 */
function array_keys_exist($array, $key1) {
  $args = func_get_args();
  array_shift($args);
  if (is_array($args[0]))
    $args = $args[0];
  foreach ($args as $val) {
    if (!array_key_exists($val, $array))
      return false;
  }
  return true;
}

/**
 * Executes $callback for every item in the array, returns TRUE if all calls returned TRUE
 * @param array $array
 * @param callable $callback
 * @return bool TRUE if all calls returned true, FALSE otherwise
 */
function array_test($array, $callback) {
  if (!is_array($array) || empty($array))
    return false;
  
  foreach ($array as $item) {
    if (!call_user_func($callback,$item))
      return false;
  }
  return true;
}

/**
 * Extracts the requested keys from $array and returns them as a new array while maintaining the original keys.
 * If $keys is an associative array, the new keys will be used for the returned array.
 * Examples:
 * <p>1. Extract values using a simple array</p>
 * <pre>
 *  array_extract(
 *    ['foo' => 'me', 'other' => 123, 'bar' => true],
 *    ['foo', 'bar']
 * );
 * </pre>
 * <p>Result: <code>['foo' => 'me', 'bar' => true]</code>
 * <p>2. Extract values using an associative array</p>
 * <pre>
 *  array_extract(
 *    ['foo' => 'me', 'other' => 123],
 *    ['bar' => 'fool']
 *  );
 * </pre>
 * <p>Result: <code>['bar' => 'me']</code></p>
 * @param array $subject The subject array
 * @param array $extract Keys to extract
 * @return array An array containing the corresponding values for $keys within the $array
 */
function array_extract($subject, $extract) {
  $return = array();
  foreach ($extract as $newKey => $selected) {
    if (array_key_exists($selected, $subject)) {
      $idx = (!is_numeric($newKey))? $newKey : $selected;
      $return[$idx] = $subject[$selected];
    }
  }
  return $return;
}

/**
 * Recursively extracts the requested keys from $array and returns them as a new array while maintaining the original keys.
 * If $extract is an associative array, the new keys will be used for the returned array (except if value is an array).
 * You can extract values from sub-arrays by including an array containing the desired sub-keys.
 * 
 * Examples:
 * <p>Extract values from a deeper dimension within the array</p>
 * <pre>
 *  array_extract(
 *    [
 *      'foo'   => 'me',
 *      'inside' => [
 *        'one',
 *        'deeper' => ['bar', 'baz']
 *      ]
 *    ],
 *    [
 *      'foo',
 *      'inside' => [
 *        'deeper' => [0]
 *      ]
 *    ]
 *  );
 * </pre>
 * <p>Result: <code>['foo' => 'me', 'inside' => ['deeper' => ['bar']]]</code></p>
 * 
 * @param array $subject The subject array
 * @param array $extract Keys to extract
 * @return array An array containing the corresponding values for $keys within the $array
 */
function array_extract_recursive($subject, $extract) {
  $return = array();
  foreach ($extract as $newKey => $selected) {
    if (!is_array($selected)) {
      // only one item selected
      if (array_key_exists($selected, $subject)) {
        $idx = (!is_numeric($newKey))? $newKey : $selected;
        $return[$idx] = $subject[$selected];
      }
    }
    else {
      // array of items requested
      if (array_key_exists($newKey, $subject)) {
        $return[$newKey] = array_extract_recursive($subject[$newKey], $selected);
      }
    }
  }
  return $return;
}

/**
 * Extracts all values for $key 'column' from a bi-dimensional array
 * 
 * Example:
 * <pre>
 *  array_pluck(array(
 *    array('blah' => 'bleh, 'foo' => 'one'),
 *    array('bar' => 'baz', 'foo' => 'two')
 *  ), 'foo');
 * </pre>
 * <p>Output: <code>array('one','two')</code></p>
 * 
 * @param array $array A bi-dimensional array
 * @param int|string $key Key name for the column
 * @param boolean $preserveKeys [optional] if TRUE, preserve keys from the original array
 * @return array An array containing the corresponding value for the $key column in each array row
 */
function array_pluck(array $array, $key, $preserveKeys = false) {
  $ret = array();
  foreach ($array as $k => $item) {
    if (is_array($item) && array_key_exists($key, $item)) {
      if ($preserveKeys)
        $ret[$k] = $item[$key];
      else
        $ret[] = $item[$key];
    }
  }
  return $ret;
}

/**
 * Returns a value from an array using a dot-separated path of keys.
 * Example this.is.a.path -> $subject['this']['is']['a']['path']
 * @param array $subject The subject array
 * @param string $path The path to look up
 * @return mixed|null The value if the key exists, null otherwise
 */
function array_get_path($subject, $path) {
  $pathArray = explode('.',$path);
    
  if (!empty($pathArray) && is_array($subject)) {
    $current = $subject;
    foreach ($pathArray as $piece) {
      if (!is_array($current) || !array_key_exists($piece, $current))
        return null;
      else
        $current = $current[$piece];
    }
    return $current;
  }
  return null;
}

/**
 * Returns the corresponding value of $key if it exists within the $array, or $else otherwise (default NULL)
 * @param array $array Haystack
 * @param string $key Needle
 * @param mixed $else [optional] Return value if needle was not found (default NULL)
 * @return mixed|NULL The value of $key within $array, or $return if not found
 */
function ifExists($array, $key, $else = null) {
  if (!is_array($array))
    return $else;
  return (array_key_exists($key, $array))? $array[$key] : $else;
}


/**
 * Returns $subject if it's not NULL nor empty string, or $else otherwise (default NULL)
 * @param mixed $subject The value being tested
 * @param mixed $else [optional] Value to return if the assertion fails
 * @param bool $assert [optional] If TRUE, return TRUE instead of $subject if the assertion is true
 * @return mixed
 */
function ifNotEmpty($subject, $else = null, $assert = false) {
  return (!is_null($subject) && $subject !== '')? ($assert? true : $subject) : $else;
}

/**
 * Returns $subject if it's not NULL, or $else otherwise (default NULL)
 * @param mixed $subject The value being tested
 * @param mixed $else [optional] Value to return if the assertion fails
 * @return mixed
 */
function ifNotNull($subject, $else = null) {
  return (!is_null($subject))? $subject : $else;
}

/**
 * Returns $subject if it's numeric, or $else otherwise (default NULL)
 * Note: $value is casted to int before being returned
 * @param mixed $subject The value being tested
 * @param mixed $else [optional] Value to return if the assertion fails
 * @return mixed
 */
function ifNumeric($subject, $else = null) {
  return (is_numeric($subject))? (int)$subject : $else; 
}

/**
 * Returns $subject if it's a valid date, or $else otherwise (default NULL)
 * @param string $subject The value being tested
 * @param mixed $else Value to return if the assertion fails
 * @return mixed
 */
function ifDate($subject, $else = null) {
  return (!empty($subject) && strtotime($subject) !== false)? $subject : $else;
}

/**
 * Returns $subject if it equals to $testValue, or $else otherwise (default NULL)
 * @param mixed $subject The value being tested
 * @param mixed $testValue Value to use as comparison
 * @param mixed $else [optional] Value to return if the assertion fails
 * @return mixed
 */
function ifEquals($subject, $testValue, $else = null) {
  return ($subject == $testValue)? $subject : $else;
}

/**
 * Returns $subject if it's *not* equal to $testValue, or $else otherwise (default NULL)
 * @param mixed $subject The value being tested
 * @param mixed $testValue Value to use as comparison
 * @param mixed $else [optional] Value to return if the assertion fails
 * @return mixed
 */
function ifNotEquals($subject, $testValue, $else = null) {
  return ($subject == $testValue)? $subject : $else;
}

/**
 * Returns $subject if it equals to any item within $possibleValues, or $else otherwise (default NULL)
 * @param mixed $subject The value being tested
 * @param array $possibleValues Possible values to test for
 * @param mixed $else [optional] Value to return if the assertion fails
 * @return mixed
 */
function ifIn($subject, $possibleValues, $else = null) {
  $assert = call_user_func_array('var_in', array_merge(array(0 => $subject), (array)$possibleValues));
  return ($assert)? $subject : $else;
}

/**
 * Returns TRUE if $key exists within $array and its value equals $value.
 * @param Array $array An array
 * @param string $key The key to test
 * @param string $value The value to test against
 * @param bool $strict [optional] TRUE to use strict comparison (default: FALSE)
 * @return boolean
 */
function array_test_equals($array, $key, $value, $strict = false) {
  if (!is_array($array))
    return false;
  if (!array_key_exists($key, $array))
    return false;
  
  return ($strict)?
    ($array[$key] === $value) : ($array[$key] == $value);
}

/**
 * Safely converts $var by reference into an array if it's not one.
 * @param mixed $var Value to cast
 * @return array A reference to $var after the conversion
 */
function &toArray(&$var) {
  if (!is_array($var))
    $var = array($var);
  
  return $var;
}

/**
 * A safer array cast when $var is or may be an object
 * @param mixed $var
 * @return array
 */
function ensureArray($var) {
  return (!is_array($var))?
    array($var) : $var;
  
}
