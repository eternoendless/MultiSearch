<?php
abstract class AbstractController {
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * The Request object associated to this controller
   * @var Request
   */
  public $request;
  
  /**
   * Id parameter (if restful)
   * @deprecated Use $request->getId()
   * @var int
   */
  public $id;
  
  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * If TRUE, incoming requests that do not specify an action will be treated
   * as RESTful requests
   * @var bool 
   */
  protected $isRestful = false;
  
  /**
   * If TRUE, catched exceptions will be returned in a restful json format instead of plain text
   * even if the request wasn't marked as restful
   * @var bool
   */
  protected $restfulResponse = true;
  
  /**
   * Name of the default action to execute if none was specified
   * @var string
   */
  protected $defaultAction = 'defaultAction';

  /**
   * Shortcut to application instance
   * @var Application
   */
  protected $app;
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  public function __construct() {
    $this->app = Application::getInstance();
  }

  /**
   * Dispatches request to the appropriate controller action according to the HTTP method.
   * 
   * @param Request $request The user-generated request
   * @param string $forceAction [optional] Force invoke this action instead of the one in the request
   * @return ResponseInterface
   */
  public function _dispatch(Request $request, $forceAction = null) {
    try {
      $this->request = $request;
      
      $action = ifNotNull($forceAction, $this->_getMethodName($request->getAction()));
      if (!$action) {
        if ($request->isRestful() || $this->isRestful)
          return $this->_dispatchRestful();
        else
          $action = $this->defaultAction;
      }
      
      // check access
      $access = $this->_checkAccess($action);
      if ($access === false)
        return new ForbiddenResponse("You're not allowed to access this resource");
      else if ($access instanceof ResponseInterface)
        return $access;

      // only allow public methods
      try {
        $reflected = new ReflectionMethod($this, $action);
        $actionFound =  ($action !== '_dispatch') && $reflected->isPublic();
      }
      catch (Exception $e) {
        $actionFound = false;
      }

      if (!$actionFound)
        return new NotFoundResponse("The requested resource could not be found");

      return $this->_callAction($action);
    }
    catch (Exception $e) {
      return $this->_handleException($e);
    }
  }
  
  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Hook to initialize things
   */
  protected function _init() {}
  
  /**
   * Prints a debug message
   * @param mixed $var Variable to dump
   * @param string $title [optional] Title (autofilled with method name if xdebug is enabled)
   * @param bool $html [optional] FALSE to avoid wrapping the dump in <pre> tags, default TRUE (autoset to FALSE if XMLHttpRequest headers are found)
   */
  protected function debug($var, $title = null, $html = null) {
    if ($html === null)
      $html = !(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
    if ($title === null && function_exists('xdebug_call_class'))
      $title = sprintf("%s::%s @ line %d", xdebug_call_class(), xdebug_call_function(), xdebug_call_line());
    Logger::debug($var, $title, $html);
  }
  
  /**
   * Gets the desired phrase with context auto-set to this controller
   * @param string $phrase Phrase name
   * @return string
   */
  protected function getPhrase($phrase) {
    return Lang::get('context.' . get_called_class() . '.' . $phrase);
  }
  
  /**
   * Template method, override it to translate the requested action name to a controller method name
   * @param string $action Action name (pulled from the request)
   * @return string Method name (public method to execute within this controller)
   */
  protected function _getMethodName($action) {
    // prevent method names starting with an underscore (reserved)
    return ($action !== '' && $action[0] == '_')?
      preg_replace('/^_+/', '', $action) : $action;
  }

  /**
   * Template method, override to implement access restrictions
   * @param string $action Action name (pulled from the request)
   * @return boolean|ResponseInterface TRUE to allow access, FALSE to deny it, ResponseInterface to redirect/show a response
   */
  protected function _checkAccess($action) {
    return true;
  }

  /**
   * Invokes the correct controller method according to the request method
   * @return ResponseInterface
   */
  protected function _dispatchRestful() {
    $map = array(
      'GET'    => 'view',
      'POST'   => 'create',
      'PUT'    => 'update',
      'DELETE' => 'destroy'
    );
    return $this->_callAction(ifExists($map, $this->request->getMethod()));
  }
  
  /**
   * Handles any unhandled exception
   * @param Exception $exception
   * @return \RestfulResponse|\ServerErrorResponse
   */
  protected function _handleException($exception) {
    if (!($exception instanceof UserException))
      Logger::log($exception);
    
    if ($this->request->isRestful() || $this->restfulResponse)
      return new RestfulResponse($exception);
    elseif ($exception instanceof UserException)
      return new HttpResponse($exception->getMessage(), $exception->getHeaders());
    else {
      $msg = '['.get_class($exception).'] '.$exception->getMessage();
      if (Configuration::isDev())
        $msg .= '<pre>'.$exception->getTraceAsString().'</pre>';
        
      return new ServerErrorResponse($msg);
    }
  }
  
  /**
   * Returns the controller's base name (SomeController -> Some)
   * @return string
   */
  protected function _getBaseName() {
    // delete "Controller" at the end and transform the first letter to lower case
    $className = get_class($this);
    return strtolower($className[0]) . substr($className, 1, -10);
  }
  
  /**
   * Returns the controller's URL
   * @param bool $full [optional] If TRUE, return the absolute URL instead of the relative URL
   * @return string Controller's URL relative to the script URL
   */
  protected function _getUrl($full = false) {
    return ($full)?
      Application::getInstance()->getBaseUrl(true).'/'.$this->_getBaseName()
      : Application::getInstance()->getScriptName().'/'.$this->_getBaseName();
  }
  
  /**
   * Invokes a controller method by name
   * @param string $action Method name
   * @return ResponseInterface
   */
  private function _callAction($action) {
    if (method_exists($this,$action))
      return $this->{$action}();
    else
      return new BadRequestResponse("The requested method does not exist: $action");
  }
  
}
