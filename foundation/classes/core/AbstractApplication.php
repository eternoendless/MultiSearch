<?php
/**
 * Application definition
 * 
 * @package FoundationPHP
 * @author Pablo Borowicz
 */
abstract class AbstractApplication {

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Full path to the Framework directory
   * @var string
   */
  public static $FRAMEWORK_PATH;
  /**
   * Full path to the Application directory (home of the framework, context, etc. directories)
   * @var string
   */
  public static $APP_PATH;
  /**
   * Full path to the application's cache directory
   * @var string
   */
  public static $CACHE_PATH;
  /**
   * Full path to the global services directory
   * @var string
   */
  public static $SERVICES_PATH;
  /**
   * Toggles the internationalization module
   * @var bool
   */
  public static $MOD_I8N = true;
  /**
   * Toggles the session module
   * @var bool
   */
  public static $MOD_SESSION = true;
  /**
   * Default language code
   * @var string
   */
  public static $DEFAULT_LANGUAGE = 'en';
  
  /**
   * Override, allows to enable/disable the use of session
   * @var boolean
   */
  public $useSession;

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Singleton instance
   * @var AbstractApplication
   */
  protected static $instance;
  
  /**
   * Name of the context in which the application should run
   * @var string
   */
  protected $context;
  
  /**
   * Full path to the Application directory (home of the framework, context, etc. directories)
   * @var string
   */
  protected $appPath;
  
  /**
   * Full path to the framework directory (usually $appPath/foundation)
   * @var string
   */
  protected $frameworkPath;
  
  /**
   * Full path to the current context (usually $appPath/contexts/$context)
   * @var string
   */
  protected $contextPath;
  
  /**
   * Full path to the entry point directory (home of the entry point index.php)
   * @var type 
   */
  protected $entryPath;
  
  /**
   * Full path to the current context's cache path (usually $contextPath/cache)
   * @var string
   */
  protected $cachePath;
  
  protected $baseURL;
  
  protected $scriptName;
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Returns the application instance
   * @return \static
   */
  public static function getInstance() {
    return static::$instance;
  }
  
  /**
   * Shorthand for new
   * @param string $context Context in which to run the application
   * @param string $entryPath Path to the application's entry point directory
   * @param string $appPath Path to the application's main directory
   * @param string $foundationPath Path to Foundation's directory
   * @return \static
   */
  public static function init($context, $entryPath, $appPath, $foundationPath) {
    return new static($context, $entryPath, $appPath, $foundationPath);
  }
  
  /**
   * Singleton constructor
   * @param string $context Context in which to run the application
   * @param string $entryPath Path to the application's entry point directory
   * @param string $appPath Path to the application's main directory
   * @param string $foundationPath Path to Foundation's directory
   * @return \static
   */
  public function __construct($context, $entryPath, $appPath, $foundationPath) {
    if (!static::$instance) {
      static::$instance = $this;
      
      $this->context = $context;
      $this->appPath = $appPath;
      $this->frameworkPath = $foundationPath;
      $this->contextPath = $this->appPath . '/contexts/' . $this->context;
      $this->cachePath = $this->contextPath . '/cache';
      $this->entryPath = $entryPath;
      
      // initialize the class loader
      ClassLoader::init(array(
        $this->contextPath.'/components',
        $this->appPath.'/components',
        $this->frameworkPath
      ));
      
      // start context if context class exists
      $contextFile = $this->contextPath . '/Context.php';
      if (file_exists($contextFile)) {
        require $contextFile;
        if (class_exists('Context')) {
          $implements = class_implements('Context');
          if (isset($implements['ContextInterface']))
            Context::start();
        }
      }
      
      Router::init($this->contextPath.'/controllers');
      
      $this->afterConstruct();
    }
    return static::$instance;
  }
  
  /**
   * Returns the current context name
   * @return string
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Returns the full path to the Application directory (home of the framework, context, etc. directories)
   * @return string
   */
  public function getAppPath() {
    return $this->appPath;
  }
  
  /**
   * Returns the full path to the framework directory
   * @return string
   */
  public function getFrameworkPath() {
    return $this->frameworkPath;
  }
  
  /**
   * Returns the full path to the current context directory
   * @return string
   */
  public function getContextPath() {
    return $this->contextPath;
  }
  
  /**
   * Returns the full path to the current cache directory
   * @return string
   */
  public function getCachePath() {
    return $this->cachePath;
  }
  
  /**
   * Returns the full path to the entry point directory
   * @return string
   */
  public function getEntryPath() {
    return $this->entryPath;
  }
  
  /**
   * Returns the application's base URL
   * @param bool [$withScript=false] TRUE to append the script name
   * @return string
   */
  public function getBaseUrl($withScript = false) {
    if (empty($this->baseURL))
      $this->baseURL = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']);
    
    $url = $this->baseURL;
    if ($withScript)
      $url .= '/'.$this->getScriptName();
    
    return $url;
  }
  
  /**
   * Returns the entry script name
   * @return string
   */
  public function getScriptName() {
    if (empty($this->scriptName))
      $this->scriptName = basename($_SERVER['SCRIPT_NAME']);
    return $this->scriptName;
  }
  
  /**
   * Sets the base URL for the application
   * @param string $baseURL
   */
  public function setBaseURL($baseURL) {
    $this->baseURL = $baseURL;
  }
  
  /**
   * Sets the script name for the application's entry point (for creating URLs)
   * @param string $scriptName
   */
  public function setScriptName($scriptName) {
    $this->scriptName = $scriptName;
  }

  /**
   * Executes the application
   */
  public function run() {
    // start output buffering to avoid sending content before headers
    ob_start();
    
    if ($this->useSession === null)
      $this->useSession = Configuration::get('@foundation.session.enabled');
    
    // initialize localization module
    $this->initLocalization();
    
    // initialize session module
    if ($this->useSession)
      $this->initSession();

    // get request
    $request = new Request();
    
    try {
      $response = Router::route($request);
    }
    catch (UserException $e) {
      switch($e->getCode()) {
        case UserException::NOT_FOUND:
          $response = (new PageNotFound($e->getMessage()))->getResponse();
          break;
        default:
          $response = ErrorPage::getOutput($e);
      }
    }

    if ($this->useSession)
      SessionService::hibernate();

    if (!is_null($response) && $response != '') {
      if ($response instanceof ResponseInterface)
        $response->send();
      else
        echo $response;
    }
    
    ClassLoader::hibernate($this->getCachePath());
    
    // flush output buffer
    ob_end_flush();
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Initializes the localization module
   */
  protected function initLocalization() {
    Lang::init();
  }
  
  /**
   * Initializes the session module
   */
  protected function initSession() {
    SessionService::start();
    // set language from session data
    $langCode = SessionService::getData('lang');
    Lang::setLangCode($langCode);
  }
  
  /**
   * Template method, invoked after constructing the application object
   */
  protected function afterConstruct() {}
  
}