<?php
/**
 * Executes a Yahoo Search
 */
class YahooSearch extends AbstractSearchEngine {
  
  /**
   * The name of this service
   */
  const SERVICE_NAME = "Yahoo";
  
  /**
   * Base search URL
   */
  const SEARCH_URL = "http://search.yahoo.com/search?p=%s&b=%d";
  
  /**
   * The base URL for Yahoo search results.
   * Some URLs from Yahoo results are relative URLs.
   * In those cases, this URL is prepended to the address 
   */
  const BASE_URL = "http://www.yahoo.com";
  
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
    
    //exit($html);
    
    $result = new SearchResult();
    $result->setCurrentPage($this->page);
    
    /* @var $resultsBlock \QueryPath\DomQuery */
    $resultsBlock = $doc->find('#web > ol > li');
    
    foreach ($resultsBlock as $blockItem) {
      //print($blockItem->html()."\n\n\n\n");
      
      $title = $blockItem->find('h3 a');
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
        'urlPreview' => $blockItem->find('.url')->innerHTML(),
        'summary' => $blockItem->find('.abstr')->innerHTML()
      ]);
      
      //Logger::debug($resultItem);
        
      $result->append($resultItem);
    }
    
    $result->hasNextPage($doc->find('#pg-next')->count() > 0);
    
    $resultsCount = $doc->find('#pg > span')->innerHTML();
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
    $start = (($this->page -1) * self::RESULTS_PER_PAGE) + 1;
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