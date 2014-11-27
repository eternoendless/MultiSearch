<?php
/**
 * Application configuration
 * 
 * @package FoundationPHP
 * @author Pablo Borowicz
 */
abstract class AbstractConfiguration {

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Name of the current server environment (default, dev, production, staging...)
   * @var string
   */
  protected static $currentEnvironment;
  
  /**
   * Default environment name
   * @var string
   */
  protected static $defaultEnvironment = 'default';
  
  /**
   * Development environment name
   * @var string
   */
  protected static $devEnvironment = 'dev';
  
  /**
   * Hostnames to automatically consider as development environments
   * @var array
   */
  protected static $devHosts = array(
    'localhost',
    '127.0.0.1',
    '/192\.168(?:\.[\d]){2}/',
    '/10(?:\.[\d]){3}/',
    '/172\.(?:1[6-9]|2[0-9]|3[01])(?:\.[\d]){2}/',
  );
  
  /**
   * Environment to host relationship. Used to automagically find out the current environment based on host name or IP address.
   * 
   * Example: ['dev' => [... dev hosts list ...], 'prod' => [... production hosts list ...]]
   * @var array
   */
  protected static $envHosts = array();

  /**
   * Applicatino settings, grouped by environment name
   * @var array
   */
  protected static $settings = array();
  
  /**
   * Determines whether the class has already been initialized or not
   * @var bool
   */
  private static $started = false;
  
  /**
   * Default settings
   * @var array
   */
  private static $defaultSettings = array(
    '@foundation' => array(
      // internationalization options
      'i18n' => array(
        // default language
        'defaultLanguage' => 'en',
        // supported languages
        'supportedLanguages' => array('en')
      ),
      // session options
      'session' => array(
        // enable/disable session management
        'enabled' => true,
        // cookie duration
        'maxCookieDuration' => 7776000, // 90 days
        // cookie domain
        'cookieDomain' => null,
        // cookie path
        'cookiePath' => null
      ),
      // log path (ex: /var/log/)
      'logPath' => null,
      // prefix to append to error logs
      'errorLogPrefix' => 'error-'
    )
  );

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Returns the desired setting for the current environment, throws an exception if not found.
   * @param string $configuration The desired settings (ex. 'db.host')
   * @param bool $allowFallback [optional] Set to FALSE to avoid falling back to defaults if the setting is not configured for the current environment (default TRUE)
   * @return mixed The configured value
   * @throws Exception
   */
  public static function get($configuration, $allowFallback = true) {
    $return = static::ifExists($configuration, $allowFallback);
    
    if ($return === null)
      throw new Exception("Undefined configuration '$configuration'");
    
    return $return;
  }
  
  /**
   * Returns the desired setting for the current environment, returns NULL if not found.
   * @param string $configuration The desired settings (ex. 'db.host')
   * @param bool $allowFallback [optional] Set to FALSE to avoid falling back to defaults if the setting is not configured for the current environment (default TRUE)
   * @return mixed The configured value, or NULL if not found
   */
  public static function ifExists($configuration, $allowFallback = true) {
    if (!self::$started) static::init();
    
    $return = static::find($configuration, static::$currentEnvironment);
    if ($return !== null || !$allowFallback)
      return $return;
    else
      return static::find($configuration, static::$defaultEnvironment);
  }

  /**
   * Returns TRUE if the current environment is 'dev'.
   * @return bool
   */
  public static function isDev() {
    if (!self::$started) static::init();
    
    return (static::$currentEnvironment == static::$devEnvironment);
  }

  /**
   * Returns the current environment name
   * @return string
   */
  public static function getCurrentEnvironment() {
    if (static::$currentEnvironment === null)
      static::$currentEnvironment = static::autoDetectEnvironment();
    
    return static::$currentEnvironment;
  }
  
  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Returns the user-defined application settings
   * @return array
   */
  protected static function getInitialSettings() {
    return array();
  }
  
  
  /**
   * Auto-detects the current environment using the local host name
   * @return string Name of the current environment
   */
  private static function autoDetectEnvironment() {
    $serverHost = $_SERVER['HTTP_HOST'];
    
    foreach (static::$envHosts as $envName => $hostList) {
      foreach ((array)$hostList as $host) {
        if ($host[0] == '/') {
          if (preg_match($host, $serverHost) === 1)
            return $envName;
        }
        elseif ($serverHost == $host)
          return $envName;
      }
    }
    
    return static::$defaultEnvironment;
  }

  /**
   * Returns the desired setting for the current environment 
   * @param string $configuration The desired setting path (ex. 'db.host')
   * @param string $environment The environment name
   * @return mixed The configured value, or NULL if not found
   */
  private static function find($configuration, $environment = null) {
    $path = explode('.',$configuration);
    
    if (!empty($path) && isset(static::$settings[$environment])) {
      $current = static::$settings[$environment];
      while ($a = array_shift($path)) {
        if (!array_key_exists($a, $current))
          return null;
        else
          $current = $current[$a];
      }
      return $current;
    }
    return null;
  }

  /**
   * Initializes the configuration
   */
  private static function init() {
    self::$started = true;
    
    // set up default settings for the default environment
    $defaultSettings = array(
      static::$defaultEnvironment => self::$defaultSettings
    );
    
    // set up environment to hosts array
    static::$envHosts = array(
      static::$devEnvironment => static::$devHosts
    );
    
    // load settings
    static::$settings = array_replace_recursive($defaultSettings, static::getInitialSettings());
    
    // set the current environment
    static::$currentEnvironment = static::getCurrentEnvironment();
    
    if (!isset(static::$settings[static::$defaultEnvironment]))
      throw new Exception('Configurations not set for the default environment ("'.static::$defaultEnvironment.'")');
  }

}