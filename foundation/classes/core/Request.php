<?php
/**
 * Represents an HTTP request
 * 
 * @package Foundation\Core
 */
class Request {
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Indicates if the request was marked as restful
   * @var bool
   */
  protected $restful;
  
  /**
   * Request HTTP method (GET, POST, PUT, DELETE)
   * @var string
   */
  protected $method;
  
  /**
   * Requested controller basename
   * @var string
   */
  protected $controller;
  
  /**
   * Name of the default controller
   * @var string
   */
  protected $defaultController = 'Main';
  
  /**
   * Requested action name (if provided)
   * @var string
   */
  protected $action;
  
  /**
   * Id parameter (if provided)
   * @var mixed 
   */
  protected $id;
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Constructs the request
   */
  public function __construct() {
    $this->restful = (isset($_GET['restful']) && $_GET['restful'] == 1);
    $this->method = $_SERVER["REQUEST_METHOD"];
    
    // parse the request path
    $requestPath = filter_input(INPUT_SERVER, 'PATH_INFO', FILTER_UNSAFE_RAW);
    if ($requestPath) {
      $cai = '/^\/([a-zA-Z]+\w)(?:\/([a-zA-Z]+\w)(?:\/([0-9]+))?)?$/';  // /controller[/action[/id]]
      $ci = '/^\/([a-zA-Z]+\w)(?:\/([0-9]+))?$/';                       // /controller[/id]
      $i =  '/^\/([0-9]+)$/';                                           // /id
      $matches = array();
      if (preg_match($cai, $requestPath, $matches)) {
        $this->setController($matches[1]);
        $this->action = ifExists($matches, 2);
        $this->id = ifExists($matches, 3);
      }
      elseif (preg_match($ci, $requestPath, $matches)) {
        $this->setController($matches[1]);
        $this->id = ifExists($matches, 2);
      }
      elseif (preg_match($i, $requestPath, $matches)) {
        $this->id = $matches[1];
      }
    }
    else {
      $this->setController($this->defaultController);
    }
  }
  
  /**
   * Returns the request HTTP method (GET, POST, PUT, DELETE)
   * @return string
   */
  public function getMethod() {
    return $this->method;
  }
  
  /**
   * Returns the requested controller basename
   * @return string
   */
  public function getController() {
    return $this->controller;
  }
  
  /**
   * Returns the requested action name
   * @return string
   */
  public function getAction() {
    return $this->action;
  }
  
  /**
   * Returns the request ID parameter (if provided)
   * @return int|NULL
   */
  public function getId() {
    return $this->id;
  }
  
  /**
   * Returns the provided data payload (if any).
   * @param string|null $root [optional] The root item (if the payload is an array)
   * @param bool $json [optional] If TRUE, evaluate the payload as a JSON string; otherwise return it as is (default: TRUE)
   * @return mixed
   */
  public function getPayload($root = null, $json = true) {
    $ret = null;
    
    $payload = false;
    
    if ($root && isset($_REQUEST[$root]) && $_REQUEST[$root] != '')
      $payload = $_REQUEST[$root];
    else {
      $raw = $this->extractRawContent();
      
      if ($raw != '') {
        if (!$root)
          $payload = $raw;
        else {
          $data = array();
          // fill $payload with the interpreted contents (exptected: param1=val1&param2=val2...)
          parse_str($raw, $data);
          
          $payload = ifNotEmpty(ifExists($data, $root));
        }
      }
    }
    
    if ($payload)
      $ret = ($json)? json_decode($payload) : $payload;
    
    return $ret;
  }

  /**
   * Returns whether the request received the RESTful parameter or not
   * @return bool
   */
  public function isRestful() {
    return $this->restful;
  }
  
  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Returns the raw contents from the request body
   * @return string
   */
  private function extractRawContent() {
    $raw = '';
    $httpContent = fopen('php://input', 'r');
    while ($kb = fread($httpContent, 1024)) {
      $raw .= $kb;
    }
    fclose($httpContent);
    return $raw;
  }
  
  /**
   * Sets the controller name
   * @param string $controller
   */
  private function setController($controller) {
    $this->controller = ucfirst($controller);
  }
  
}

