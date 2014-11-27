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
  
  private $resultsCount;
  
  /**
   *
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
   * Sets the current page number
   * @param int $currentPage
   */
  public function setCurrentPage($currentPage) {
    $this->currentPage = $currentPage;
    return $this;
  }
  
  public function setResultsCount($resultsCount) {
    $this->resultsCount = $resultsCount;
    return $this;
  }
  
  public function setOffset($offset) {
    $this->offset = $offset;
    return $this;
  }

  /**
   * Returns the current page number
   * @return int
   */
  public function getCurrentPage() {
    return $this->currentPage;
  }
  
  public function getResultsCount() {
    return $this->resultsCount;
  }

  public function getOffset() {
    return $this->offset;
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////


}