<?php
abstract class Model {

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Specifies if this is a phantom model (ie nonexistent in the DB)
   * @var bool (default TRUE)
   */
  public $phantom = true;

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////

  /**
   * The model's attributes (data)
   * @var array|null
   */
  protected $attributes;
  
  /**
   * The model's dirty (changed but unsaved) attributes
   * @var array|null
   */
  protected $dirtyAttributes = array();
  /**
   * Model's table name
   * @var string (default [empty string])
   */
  protected static $tableName = '';
  /**
   * Primary key to the model's table (only one field)
   * @var string (default [empty string])
   */
  protected static $pk = '';
  /**
   * Let the database driver set the primary key value of a new instance
   * @var boolean
   */
  protected static $autosetPkValue = true;
  
  /**
   * Champs de la table
   * @var array
   */
  protected static $fieldsDefinition = array();
  
  /**
   * Toggles 'soft deletion', which updates the deleted attribute instead of actually deleting
   * @var bool (default FALSE)
   */
  protected static $softDelete = false;
  /**
   * Toggles 'read only' mode, preventing the update/deletion of the model
   * @var bool (default FALSE)
   */
  protected static $readOnly = false;
  
  protected static $debug = false;
  
  /**
   * @readonly
   * @var array 
   */
  protected static $fields = array();
  protected static $initialized = false;

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Returns the model's PK name
   * @return string|null
   */
  public static function getPk() {
    return static::$pk;
  }
  
  /**
   * Returns the model's table name
   * @string $alias [optional] If provided, append this alias to the table name
   * @return string
   */
  public static function getTableName($alias = '') {
    return empty($alias)?
      static::$tableName
      : (static::$tableName.' '.$alias);
  }
  
  /**
   * Returns a query with table name and model properties already set
   * @param string|array|null $fields [optional] Fields to select
   * @param string $tableAlias [optional] Alias for the model's table. If specified, this will be prepended to every $field
   * @return \Query
   */
  public static function getBasicQuery($fields = null, $tableAlias = '') {
    $query = new Query(array(
      'from'  => static::$tableName . (!empty($tableAlias)? " as $tableAlias" : ''),
      'model' => get_called_class()
    ));
    
    if ($fields !== null)
      if (!empty($tableAlias)) {
        $fields = (array)$fields;
        foreach ($fields as $i => $field) {
          $fields[$i] = "$tableAlias.$field";
        }
      }
      $query->add(array('fields' => $fields));
    
    return $query;
  }

  /**
   * Creates a new instance of this model
   * @param array $params Contents
   * @param bool $autoSave [optional] TRUE to save the model after creation (defaults to false)
   * @return \static
   */
  public static function create($params, $autoSave = false) {
    $obj = new static($params);
    if ($autoSave) {
      return ($obj->save())?
        $obj : null;
    }
    return $obj;
  }
  
  /**
   * Finds all instances of model in the database
   * @param string $orderBy [optional] ORDER BY criteria
   * @param int|string $limit [optional] LIMIT criteria
   * @return ModelCollection|null
   */
  public static function all($orderBy = null, $limit = null) {
    $where = (static::$softDelete)? 'DELETED = 0' : null;
    return static::findBySQL($where, $orderBy, $limit);
  }
  
  /**
   * Finds the model whose PK matches $id
   * @param int|array $id
   * @param bool $returnModelCollection [optional] If TRUE, return a ModelCollection instead of a Model instance (default: FALSE). Otherwise, return only the first result.
   * @param string|array $fields [optional] if provided, only populate the selected fields (note: PKs are always populated)
   * @return \static|ModelCollection|null
   */
  public static function find($id, $returnModelCollection = false, $fields = null) {
    if (empty(static::$pk))
      throw new Exception("Cannot execute search because ".get_called_class()." has no defined primary key");
    
    $result = static::findBy(static::$pk, $id, null, null, $fields);
    if ($result && !$returnModelCollection)
      return $result->getFirst();
    return $result;
  }
  
  /**
   * Finds the models where $key = $value
   * @param string $key Field name
   * @param mixed $value Value or values to look for
   * @param string|array $fields [optional] if provided, populate the selected fields only (note: PKs are populated always)
   * @return ModelCollection|null
   */
  public static function findBy($key, $value, $orderBy = null, $limit = null, $fields = null) {
    $db = Database::get();
    
    $where = $key;
    
    if (is_array($value))
      $where .= ' IN('.implode(',',array_map(array($db,'quote'),$value)).')';
    else
      $where .= ' ='.$db->quote($value);
    
    if (static::$softDelete)
      $where .=' AND DELETED = 0';
    
    return static::findBySQL($where, $orderBy, $limit, $fields);
  }
  
  /**
   * Finds the models matching the provided SQL parameters.
   * @param string $where
   * @param string $orderBy [optional]
   * @param string $limit [optional]
   * @param string|array $fields [optional] if provided, populate the selected fields only (note: PKs are populated always)
   * @return ModelCollection|null
   */
  public static function findBySQL($where, $orderBy = null, $limit = null, $fields = null) {
    if (!is_null($limit))
      $limit = " LIMIT $limit";
    
    if (!empty($orderBy))
      $orderBy = " ORDER BY $orderBy";
    
    if (!empty($where))
      $where = " WHERE $where";
    
    if (!empty($fields)) {
      $fields = (array)$fields;
      
      // the PK must be in the field list, add it if it's missing
      if (!empty(static::$pk) && !in_array(static::$pk, $fields))
        array_unshift($fields, static::$pk);
      
      $fields = implode(', ',$fields);
    }
    else {
      $fields = '*';
    }
    
    $sql = "SELECT $fields FROM "
      .static::$tableName
      .$where
      .$orderBy
      .$limit;
    
    if (static::$debug)
      Logger::debug($sql);
    
    return static::constructFromSQL($sql);
  }
  
  /**
   * Finds the models matching the provided Query object
   * @param Query $query
   * @return ModelCollection|null
   */
  public static function findByQuery(Query $query) {
    $sql = $query->toSql();
    
    if (static::$debug)
      Logger::debug($sql);
    
    return static::constructFromSQL($sql);
  }

  /**
   * Returns the number of model entries in the DB that match the provided parameters
   * @param string $where
   * @param string $limit [optional]
   * @return int
   */
  public static function findCount($where, $limit = null) {
    $db = Database::get();
    if (!is_null($limit))
      $limit = "LIMIT $limit";
    $rs = $db->query("SELECT 1 FROM ".static::$tableName." WHERE $where $limit");
    return $rs->rowCount();
  }
  
  /**
   * Returns the number of model entries in the DB that match the provided query
   * @param Query $query
   * @return int
   */
  public static function findCountByQuery(Query $query) {
    $query->set(array('fields' => '1'));
    
    $sql = $query->toSql();
    
    if (static::$debug)
      Logger::debug($sql);
    
    $db = Database::get();
    
    $rs = $db->query($sql);
    return $rs->rowCount();
  }
  
  /**
   * Returns TRUE if there's at least one record whose PK matches $value
   * @param int $value
   * @return bool
   */
  public static function exists($value) {
    return static::findCount(self::$pk . ' = '.(int)$value) > 0;
  }
  
  /**
   * Updates $attributes from all models matching $id and $extra 
   * @param int|array $id
   * @param array $attributes Attributes to update
   * @param string $extra [optional] extra SQL to add to the request (should start with a boolean operator)
   * @param boolean $returnInstances [optional] (default: TRUE) TRUE to return the modified instances, FALSE to return the number of affected rows
   * @return Model|ModelCollection|null|int
   */
  public static function update($id, $attributes, $extra = null, $returnInstances = true) {
    if (static::$pk == '')
      throw new Exception("Cannot execute modification because ".get_called_class()." does not have a PK");
    
    if (static::$readOnly)
      throw new Exception("Cannot execute modification because ".get_called_class()." is read-only");
    
    if ($returnInstances && !$extra) {
      $instances = static::find($id, true);
      $returnCollection = is_array($id);
      
      if ($instances == null)
        return null;

      foreach ($instances as $instance) {
        $instance->updateAttributes($attributes);
        $instance->save();
      }
    
      return ($returnCollection)?
        $instances : $instances->getFirst();
    }
    else {
      $whereId = (is_array($id))?
        Query::buildInStatement($id) : (' = '.(int)$id);

      $where = static::$pk . $whereId .' '. (string)$extra;
      return static::updateWhere($attributes, $where, $returnInstances);
    }
  }
  
  /**
   * Updates $attributes from all models matching $where
   * @param array $attributes Attributes to update
   * @param string $where SQL where for the request
   * @param boolean $returnInstances [optional] (default: TRUE) TRUE to return the modified instances, FALSE to return the number of affected rows
   * @return ModelCollection|null|int
   */
  public static function updateWhere($attributes, $where, $returnInstances = true) {
    if (static::$readOnly)
      throw new Exception("Cannot execute modification because ".get_called_class()." is read-only");
    
    $db = Database::get();
    
    if (!$returnInstances) {
      $sql = Query::buildUpdateSQL(static::$tableName, $attributes, $where);
      return $db->exec($sql);
    }
    
    $instances = static::findBySQL($where);

    if ($instances == null)
      return null;
    
    foreach ($instances as $instance) {
      $instance->updateAttributes($attributes);
      $instance->save();
    }
    
    return $instances;
  }

  /**
   * Destroys the model that matches $id
   * @param int|Model $id Id or Model instance
   * @param string $extra [optional] extra SQL to add to the request (should start with a boolean operator)
   * @return int|Model Affected records count
   */
  public static function destroy($id, $extra = null) {
    if (is_object($id)) {
      if (!($id instanceof static))
        throw new Exception("Cannot delete: object must be an instance of ".get_called_class()." (was ".get_class($id).")");

      $id = $id->getId();
    }
    
    if (empty(static::$pk))
      throw new Exception("Cannot delete because ".get_called_class()." does not have a primary key");
    
    if (static::$readOnly)
      throw new Exception("Cannot delete because ".get_called_class()." is read-only");
    
    if (empty($id))
      return 0;
    
    $db = Database::get();
    if (static::$softDelete) {
      return static::update($id, array('DELETED' => 1), $extra, false);
    }
    else {
      $whereId = (is_array($id))?
        Query::buildInStatement($id) : (' = '.(int)$id);
      $where = static::$pk .$whereId .' '. (string)$extra;
      
      $sql = 'DELETE FROM '.static::$tableName." WHERE $where";
      return $db->exec($sql);
    }
  }
  
  // ---- INSTANCE METHODS ------------------------------------------------------------------------
  
  /**
   * Creates an instance of the model and fills it with $attributes
   * @param array $attributes
   */
  public function __construct($attributes) {
    static::init();
    
    // transform all keys to upper case
    $attributes = (is_object($attributes))? get_object_vars($attributes) : $attributes;
    $instanceAttr = array();
    $fieldsEmpty = empty(static::$fields);
    foreach ($attributes as $key => $value) {
      $attrKey = strtoupper($key);
      // only add if no field definition exists, or if the field is found
      if ($fieldsEmpty || array_key_exists($attrKey, static::$fields))
        $instanceAttr[$attrKey] = $value;
    }
    
    $this->attributes = $instanceAttr;
  }

  /**
   * Saves the model to the database
   * @return bool
   */
  public function save() {
    if ($this->phantom) {
      if (static::$pk) {
        if (static::$autosetPkValue)
          unset($this->attributes[static::$pk]);
        else if (!isset($this->attributes[static::$pk]))
          throw new Exception("Cannot save a new instance of ".get_called_class()." without a value for ".static::$pk);
      }
      
      $id = $this->execInsert();
      if (!$id)
        return false;
      
      if (static::$pk)
        $this->attributes[static::$pk] = $id;
      
      return true;
    }
    else {
      return ($this->execUpdate() !== false);
    }
  }
  
  /**
   * Destroys this object in the db
   * @return bool
   */
  public function selfDestroy() {
    $success = (bool)static::destroy($this);
    if ($success && !static::$softDelete)
      unset($this);
    return $success;
  }
  
  /**
   * Returns the model's attributes. If an array was provided, only the requested attributes are returned.
   * If the provided array contains non-numeric keys, they are used as keys for the values
   * (example: array('alias' => 'foo') returns the 'foo' attribute value under the 'alias' key
   * @param array $attributes [optional] If provided, only return these attributes
   * @return array
   */
  public function getAttributes($attributes = null, $allowNull = true) {
    if ($attributes === null)
      return ($allowNull)? $this->attributes : null;
    
    if (is_string($attributes))
      return ifExists($this->attributes, $attributes);
    
    $ret = array();
    foreach (toArray($attributes) as $key => $name) {
      if (array_key_exists($name, $this->attributes))
        $ret[(!is_numeric($key))? $key : $name] = $this->attributes[$name];
    }
    return $ret;
  }
  
  /**
   * Returns a model attribute by name
   * @param string|array $name
   * @return mixed|NULL
   */
  public function getAttribute($name) {
    return $this->getAttributes($name, false);
  }
  
  /**
   * Returns all the model's attributes EXCEPT those provided
   * @param string|array $except
   * @return array
   */
  public function getAttributesExcept($except) {
    $attr = $this->attributes;
    
    foreach ((array)$except as $item) {
      if (array_key_exists($item, $attr))
        unset($attr[$item]);
      
      return $attr;
    }
  }
  
  /**
   * Returns TRUE if an attribute $name exists in this model
   * @param string $name
   * @return bool
   */
  public function attributeExists($name) {
    return array_key_exists($name, $this->attributes);
  }
  
  /**
   * Updates one or many model attributes
   * @param string|array $attribute
   * @param mixed $value [optional]
   * @return int Updated fields count
   */
  public function setAttribute($attribute, $value = null) {
    return $this->updateAttributes(is_array($attribute) ? $attribute : array($attribute => $value));
  }
  
  /**
   * @alias setAttribute
   * Updates one or many model attributes
   * @param string|array $attribute
   * @param mixed $value [optional]
   * @return int Updated fields count
   */
  public function set($attribute, $value = null) {
    return $this->setAttribute($attribute, $value);
  }
  
  /**
   * Updates the model's attributes.
   * This function only accepts fields that already exist in the model (except for the PK, which is ignored)
   * whose values are different from current ones, or that exist in the field definition.
   * @param array $attributes [FIELD_NAME => value] pairing
   * @return int Updated fields count
   */
  public function updateAttributes($attributes) {
    $changedCount = 0;
    foreach ($attributes as $field => $value) {
      $fieldName = strtoupper($field);
      if ($fieldName != static::$pk) {
        if (array_key_exists($fieldName, $this->attributes)) {
          if ($this->attributes[$fieldName] == $value)
            continue;
        }
        elseif (empty(static::$fields) || !array_key_exists($fieldName, static::$fields))
          continue;
        
        $this->attributes[$fieldName] = $value;
        
        // track the changed attributes
        if (!$this->phantom)
          $this->dirtyAttributes[$fieldName] = $value;
        
        $changedCount++;
      }
    }
    return $changedCount;
  }
  
  /**
   * Returns the model's PK
   * @return int
   */
  public function getId() {
    return $this->attributes[static::$pk];
  }
  
  /**
   * @alias getAttribute
   * @param string|array $attribute
   * @return mixed|NULL
   */
  public function get($attribute) {
    return $this->getAttributes($attribute, false);
  } 
  
  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Initializes the class
   */
  protected function init() {
    if (!static::$initialized) {
      if (!empty(static::$fieldsDefinition)) {
        foreach (static::$fieldsDefinition as $field) {
          static::$fields[$field] = true;
        }
      }
      static::$initialized = true;
    }
  }

  /**
   * Saves the model by executing the an insert statement
   * @return int|false This function returns either the last inserted id (if a PK was defined) or TRUE on success, and FALSE otherwise
   */
  protected function execInsert() {
    $fields = implode(',', array_map(function($item){
      return "`$item`";
    },array_keys($this->attributes)));
    
    $params = array();
    foreach ($this->attributes as $field => $value) {
      $params[':'.$field] = $value;
    }
    
    $sql = 'INSERT INTO '.static::$tableName." ($fields) VALUES (".implode(',',array_keys($params)).")";
    
    $db = Database::get();
    $rs = $db->prepare($sql);
    if (!$rs->execute($params))
      return false;
    else {
      return (static::$pk)?
        $db->lastInsertId() : true;
    }
  }

  /**
   * Executes the update statement to update the model
   * @param string $where [optional] If not specified, use the PK
   * @return int Number of rows that were modified
   */
  protected function execUpdate($where = null) {
    $db = Database::get();
    
    $pk = static::$pk;
    
    $fields = $this->dirtyAttributes;
      
    if (empty($fields))
      return 0;
    
    if ($where == null) {
      $where = "$pk = ". $db->quote($this->getId());
    }
    
    $sql = Query::buildUpdateSQL(static::$tableName, $fields, $where);
    
    $result = $db->exec($sql);
    
    // clean up dirty attributes
    if ($result !== false)
      $this->dirtyAttributes = array();
    
    return $result;
  }
  
  /**
   * Constructs model instances from an SQL statement
   * @param string $sql SQL statement
   * @return ModelCollection|null
   */
  protected static function constructFromSQL($sql) {
    $db = Database::get();
    
    $rs = $db->query($sql);
    
    if ($rs->rowCount() == 0)
      return null;
    else
      return static::constuctInstances($rs->fetchAll());
  }
  
  /**
   * Constructs Model instances from an array of model data
   * @param array $array
   * @return ModelCollection
   */
  protected static function constuctInstances($array) {
    $instances = new ModelCollection();//array();
    foreach ($array as $row) {
      $new = new static($row);
      $new->phantom = false;
      $instances[] = $new;
    }
    return $instances;
  }
  
}