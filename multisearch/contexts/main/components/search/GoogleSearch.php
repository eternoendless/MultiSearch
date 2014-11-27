<?php
/**
 * Executes a Google Search
 */
class GoogleSearch extends AbstractSearchEngine {
  
  /**
   * The name of this service
   */
  const SERVICE_NAME = "Google";
  
  /**
   * Base search URL
   */
  const SEARCH_URL = "http://www.google.com/search?q=%s&start=%d";
  
  /**
   * The base URL for Google search results.
   * Google search results' URLs aren't direct links to the external address,
   * but *relative* links to a Google address that receives the external address
   * as a parameter.
   * This URL is prepended to the address if the external address cannot be extracted.
   */
  const BASE_URL = "http://www.google.com";
  
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
    
    $result = new SearchResult();
    $result->setCurrentPage($this->page);
    
    /* @var $resultsBlock \QueryPath\DomQuery */
    $resultsBlock = $doc->find('#search li.g');
    
    foreach ($resultsBlock as $blockItem) {
      //print($blockItem->html()."\n\n\n\n");
      
      $title = $blockItem->find('h3 > a');
      
      // extract item URL
      $matchedUrl = html_entity_decode($title->attr('href'));
      // try and extract the real url
      if (preg_match('/\/\?q=(.+)&sa=/', $matchedUrl, $matches))
        $url = $matches[1];
      else
        $url = self::BASE_URL . $matchedUrl;
      
      $resultItem = new SearchResultItem([
        'title' => $title->innerHTML(),
        'url'   => $url,
        'urlPreview' => $blockItem->find('cite')->innerHTML(),
        'summary' => $blockItem->find('span.st')->innerHTML()
      ]);
      
      //Logger::debug($resultItem);
        
      $result->append($resultItem);
    }
    
    $result->hasNextPage($doc->find('#nav td:last-child a')->count() > 0);
    
    $resultsCount = $doc->find('#resultStats')->innerHTML();
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