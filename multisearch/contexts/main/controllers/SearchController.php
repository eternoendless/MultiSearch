<?php
/**
 * Search controller.
 * 
 * Handles queries to search providers
 * 
 * @package MultiSearch
 * @author Pablo Borowicz
 */
class SearchController extends AbstractController {
  use TwigTemplate;

  const GOOGLE = 'GoogleSearch';
  const BING = 'BingSearch';
  const YAHOO = 'YahooSearch';
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Supported services
   * @var array
   */
  private $services = [
    self::GOOGLE,
    self::BING,
    self::YAHOO
  ];

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Executes a Google Search
   * @return ResponseInterface
   */
  public function google() {
    return $this->_search(self::GOOGLE);
  }
  
  /**
   * Executes a Bing Search
   * @return ResponseInterface
   */
  public function bing() {
    return $this->_search(self::BING);
  }
  
  /**
   * Executes a Yahoo Search
   * @return ResponseInterface
   */
  public function yahoo() {
    return $this->_search(self::YAHOO);
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Executes a search
   * @param string $service Search engine name (from self->$serviecs)
   * @return ResponseInterface
   * @throws UnexpectedValueException
   */
  private function _search($service) {
    if (!in_array($service, $this->services))
      throw new UserException("The requested service is not supported", UserException::BAD_PARAMS);
    
    $params = $_GET;
    
    $queryString = ifExists($params, 'q'); 
    $page = ifNumeric(ifExists($params, 'page'), 1);
    if ($page < 1)
      $page = 1;
    
    /* @var $search AbstractSearchEngine */
    $search = new $service();
    $result = $search->query($queryString, $page);
    
    return $this->_showResults($result);
  }
  
  /**
   * Prints the search results
   * @param SearchResult $result The search result object
   * @return \HtmlResponse
   */
  private function _showResults(SearchResult $result) {
    $twig = $this->_getTwig();
    
    return new HtmlResponse(
      $twig->render(($result->count() > 0)? 'searchResults.twig' : 'noResults.twig', [
        'result' => $result
      ])
    );
  }

}