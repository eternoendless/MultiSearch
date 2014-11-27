<?php

class SearchResult extends ArrayObject {

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Indicates if the search result has a next page
   * @var bool
   */
  private $hasNextPage = false;
  
  /**
   * The current page number
   * @var int
   */
  private $currentPage;
  
  /**
   * The search query
   * @var string
   */
  private $query;
  
  /**
   * A string showing the number of results
   * @var string
   */
  private $resultsCount;
  
  /**
   * Results offset
   * @var int
   */
  private $offset;

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Sets/gets whether there is a next page in this result set.
   * @param bool $hasNextPage If provided, sets this value
   * @return bool
   */
  public function hasNextPage($hasNextPage = null) {
    if ($hasNextPage !== null)
      $this->hasNextPage = (bool)$hasNextPage;
    return $this->hasNextPage;
  }

  /**
   * Sets the query for this search
   * @param string $query
   * @return \SearchResult
   */
  public function setQuery($query) {
    $this->query = $query;
    return $this;
  }

  /**
   * Sets the current page number
   * @param int $currentPage
   */
  public function setCurrentPage($currentPage) {
    $this->currentPage = $currentPage;
    return $this;
  }
  
  /**
   * Sets the results count
   * @param string $resultsCount
   * @return \SearchResult
   */
  public function setResultsCount($resultsCount) {
    $this->resultsCount = $resultsCount;
    return $this;
  }
  
  /**
   * Sets the results offset
   * @param int $offset
   * @return \SearchResult
   */
  public function setOffset($offset) {
    $this->offset = $offset;
    return $this;
  }
  
  /**
   * Returns the query for this search
   * @return string
   */
  public function getQuery() {
    return $this->query;
  }

  /**
   * Returns the current page number
   * @return int
   */
  public function getCurrentPage() {
    return $this->currentPage;
  }
  
  /**
   * Returns the number of results for this search
   * @return string
   */
  public function getResultsCount() {
    return $this->resultsCount;
  }

  /**
   * Returns the offset for this result set
   * @return int
   */
  public function getOffset() {
    return $this->offset;
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////


}