<?php
/**
 * A collection of models
 */
class ModelCollection extends ArrayObject {

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Returns this collection as an array containing only the models' attributes.
   * If an array was provided, returns only the requested attributes.
   * If the provided array contains non-numeric keys, they are used as keys for the values
   * (example: array('alias' => 'foo') returns the 'foo' attribute value under the 'alias' key
   * @param array [optional] If provided, only return these attributes
   * @return array
   */
  public function getAsAttributeCollection($attributes = null) {
    return array_map(function($item) use ($attributes){
      return $item->getAttributes($attributes);
    }, $this->getArrayCopy());
  }
  
  /**
   * Returns the first object in this collection
   * @return Model
   */
  public function getFirst() {
    return $this->offsetGet(0);
  }
  
  /**
   * Indexes the collection using a specific field from the models (note that duplicates overlap each other)
   * @param string $fieldName
   * @return \ModelCollection
   */
  public function indexBy($fieldName) {
    if ($this->count() == 0)
      return $this;
    
    $indexed = array();
    $original = $this->getArrayCopy();
    foreach ($original as $item) {
      $indexed[$item->get($fieldName)] = $item;
    }
    $this->exchangeArray($indexed);
    return $this;
  }
  
  /**
   * Returns a copy of the collection, grouped by a specific column
   * @param string $fieldName Name of the field whose value is to be used for grouping
   * @return array An array of grouped ModelCollections { val1: [ ModelCollection ], val2: [ ModelCollection ] }
   */
  public function getGroupedBy($fieldName) {
    if ($this->count() == 0)
      return $this;
    
    $grouped = array();
    $original = $this->getArrayCopy();
    foreach ($original as $item) {
      $value = $item->get($fieldName);
      if (!isset($grouped[$value]))
        $grouped[$value] = new ModelCollection(array());
      
      $grouped[$value]->append($item);
    }
    return $grouped;
  }

  /**
   * Returns a copy of the collection filtered by a callback function
   * @param callable $callback A function that receives a Model as a parameter and returns a boolean
   * @return \ModelCollection
   */
  public function filter(callable $callback) {
    $filtered = new self();
    foreach ($this as $item) {
      if ($callback($item))
        $filtered->append($item);
    }
    return $filtered;
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////


}