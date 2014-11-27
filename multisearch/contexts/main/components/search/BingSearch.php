<?php
/**
 * Executes a Bing Search
 */
class BingSearch extends AbstractSearchEngine {
  
  /**
   * The name of this service
   */
  const SERVICE_NAME = "Bing";
  
  /**
   * Base search URL
   */
  const SEARCH_URL = "http://www.bing.com/search?q=%s&first=%d";
  
  /**
   * The base URL for Google search results.
   * Google search results' URLs aren't direct links to the external address,
   * but *relative* links to a Google address that receives the external address
   * as a parameter.
   * This URL is prepended to this address if the external address cannot be extracted.
   */
  const BASE_URL = "http://www.bing.com";
  
  /**
   * The number of search results per page returned by this engine
   */
  const RESULTS_PER_PAGE = 10;
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * The search query
   * @var string
   */
  private $query;
  
  /**
   * The requested page
   * @var int
   */
  private $page;

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Executes a search
   * @param string $queryString The search query
   * @param int $page The page number
   * @return ResponseInterface
   */
  public function query($queryString, $page = 1) {
    $this->query = $queryString;
    $this->page = $page;
    
    return $this->getQueryResults();
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Executes a search
   * @return \SearchResult A set of search results
   */
  private function getQueryResults() {
    $html = $this->getHTML();
    
    /* @var $doc QueryPath */
    
    $doc = $this->getQueryPath($html);
    //$doc = htmlqp($html);
    
    //exit($html);
    
    $result = new SearchResult();
    $result->setCurrentPage($this->page);
    
    /* @var $resultsBlock \QueryPath\DomQuery */
    $resultsBlock = $doc->find('#b_results > li');
    
    foreach ($resultsBlock as $blockItem) {
      //print($blockItem->html()."\n\n\n\n");
      
      $title = $blockItem->find('h2 a');
      if ($title->count() == 0)
        continue;
      
      // extract item URL
      $matchedUrl = html_entity_decode($title->attr('href'));
      // urls are absolute unless it's a bing resource
      if (preg_match('/^\//', $matchedUrl))
        $url = self::BASE_URL . $matchedUrl;
      else
        $url = $matchedUrl;
      
      $resultItem = new SearchResultItem([
        'title' => $title->innerHTML(),
        'url'   => $url,
        'urlPreview' => $blockItem->find('.b_attribution cite')->innerHTML(),
        'summary' => $blockItem->find('.b_caption p')->innerHTML()
      ]);
      
      //Logger::debug($resultItem);
        
      $result->append($resultItem);
    }
    
    $result->hasNextPage($doc->find('.b_pag li:last-child a.sb_pagN')->count() > 0);
    
    $resultsCount = $doc->find('.sb_count')->innerHTML();
    if (!empty($resultsCount)) {
      $result->setResultsCount($resultsCount);
    }
    
    $result->setOffset((($this->page -1) * self::RESULTS_PER_PAGE) + 1); 
    
    return $result;
  }
  
  /**
   * Composes the search URL using the query string and the page number
   * @return string The service's search URL
   */
  private function buildURL() {
    $start = ($this->page -1) * self::RESULTS_PER_PAGE;
    return sprintf(self::SEARCH_URL, urlencode($this->query), $start);
  }
  
  /**
   * Retrieves the HTML code from the remote service
   * @return string HTML
   * @throws Exception
   */
  private function getHTML() {
    $url = $this->buildURL();
    
    $html = file_get_contents($url);
    if ($html === false)
      throw new RuntimeException("Could not retrieve the search results from ".self::SERVICE_NAME);
    
    return $html;
  }

}