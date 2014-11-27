<?php
/**
 * Provides language abstraction functionality
 * 
 * @package Foundation
 */
class Lang {

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  
  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Current language code
   * @var string
   */
  protected static $langCode;
  
  /**
   * Cached dictionary of phrases
   * @var array
   */
  protected static $dictionary = array();
  
  /**
   * List of supported language codes
   * @var array
   */
  protected static $supportedLanguages;
  
  /**
   * Default language code
   * @var string
   */
  protected static $defaultLanguage;
  
  /**
   * Indicates if the class has been initalized
   * @var bool
   */
  private static $intialized = false;

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Initializes the object
   */
  public static function init() {
    if (self::$intialized)
      throw new Exception("Localization has already been initialized");
    
    self::$intialized = true;
    self::$defaultLanguage = Configuration::get('@foundation.i18n.defaultLanguage');
    self::$supportedLanguages = Configuration::get('@foundation.i18n.supportedLanguages');
  }

  /**
   * Returns whether $langCode is within the supported languages
   * @param string $langCode
   * @return bool
   */
  public static function isSupported($langCode) {
    self::checkInit();
    
    return in_array($langCode,self::$supportedLanguages);
  }

  /**
   * Sets the current language
   * @param string $code Language code
   * @return mixed Language code|FALSE
   */
  public static function setLangCode($code) {
    self::checkInit();
    
    if ($code !== null && $code != self::$langCode && self::isSupported($code)) {
      $localeName = "/locale/$code.php";
      $app = Application::getInstance();
      $success = false;
      
      foreach (array(
        'foundation' => $app->getFrameworkPath(),
        'app'        => $app->getAppPath(),
        'context'    => $app->getContextPath()
      ) as $namespace => $basePath) {
        $imported = self::import($namespace, $basePath.$localeName);
        $success = $success || $imported;
      }
      
      if ($success) {
        self::$langCode = $code;
        
        return $code;
      }
    }
    return false;
  }
  
  /**
   * Returns the current language code
   * @return type Language code
   */
  public static function getCurrentLangCode() {
    self::checkInit();
    
    if (self::$langCode === null) {
      // Try auto-setting it
      self::setLangCode(self::getBrowserLanguage());
    }
    return self::$langCode;
  }
  
  /**
   * Returns the requested phrase in the configured language
   * 
   * Phrases are organized as a tree structure with three main branches:
   * - foundation
   * - app
   * - context
   * 
   * Lookup formats are as follows:
   * - foundation.PHRASE_NAME --> PHRASE_NAME in the foundation dictionary
   * - app.PHRASE_NAME --> PHRASE_NAME in the app global dictionary
   * - [context.]PHRASE_NAME (with the optional "context." prefix) -->  PHRASE_NAME in the current context dictionary
   * 
   * Additionally, you can search in subpaths (if defined):
   * - [context|$].path.to.PHRASE --> if the path starts with a dot or $, retrieve within the context dictionary
   * - [foundation.|app.|context.][branch.]* --> A whole branch
   * 
   * @param string $phrase The desired phrase using the following format [context|app|foundation.][something.]PHRASE_NAME
   * @param mixed [optional] Replacement argumentsto pass on to sprintf if the phrase is a template
   * @return string|array Phrase text, replaced template or 'PHRASE_NAME' if the phrase was not found
   */
  public static function get($phrase, $replacements = null) {
    self::checkInit();
    
    if (self::$langCode === null) {
      // Try auto-setting it
      self::setLangCode(self::getBrowserLanguage());
    }
    
    $text = self::find($phrase);
    return (!is_null($replacements))?
      call_user_func_array('sprintf', array_merge([$text], ensureArray($replacements)))
      : $text;
  }
  
  /**
   * Returns the best supported language code matching the browser-accepted languages
   * @return string Language code
   */
  public static function getBrowserLanguage() {
    self::checkInit();
    
    $language = self::$defaultLanguage;

    if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
      $languages = strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
      $languages = explode(",", $languages);

      foreach ($languages as $language_list) {
        $lang = substr($language_list, 0, 2); // cut out primary language 'en-US' -> 'en'
        if (in_array($lang, self::$supportedLanguages))
          return $lang;
      }

    }
    return $language;
  }
  
  /**
   * Returns the supported languages list
   * @return array
   */
  public static function getSupportedLanguages() {
    return self::$supportedLanguages;
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  private static function checkInit() {
    if (!self::$intialized)
      throw new Exception("Localization should be initialized before use");
  }

  /**
   * Returns the desired phrase
   * 
   * Phrases are organized as a tree structure with three main branches:
   * - foundation
   * - app
   * - context
   * 
   * Lookup formats are as follows:
   * - foundation.PHRASE_NAME --> PHRASE_NAME in the foundation dictionary
   * - app.PHRASE_NAME --> PHRASE_NAME in the app global dictionary
   * - [context.]PHRASE_NAME (with the optional "context." prefix) -->  PHRASE_NAME in the current context dictionary
   * 
   * Additionally, you can search in subpaths (if defined):
   * - [context|$].path.to.PHRASE --> if the path starts with a dot or $, retrieve within the context dictionary
   * - [foundation.|app.|context.][branch.]* --> A whole branch
   * 
   * @param string $phrasePath The desired phrase path using the following format [context|app|foundation.][something.]PHRASE_NAME
   * @return string|array Phrase text, or 'PHRASE_NAME'
   */
  private static function find($phrasePath = '') {
    if (empty($phrasePath))
      return '';
    
    $node = self::$dictionary;
    
    // return the entire dictionary
    if ($phrasePath == '*')
      return $node;

    $path = explode('.',$phrasePath);
    
    // if there's only PHRASE -> context.PHRASE
    if (count($path) == 1) {
      return (isset($node['context']) && isset($node['context'][$phrasePath]))?
        $node['context'][$phrasePath] : $phrasePath;
    }
    
    // autocomplete with current context if the path starts with a dot or $.
    // example: .path.to.PHRASE || $.path.to.PHRASE --> context.path.to.PHRASE
    if (var_in($path[0], '', '$'))
      $path[0] = 'context';
    
    // traverse the dictionary
    while ($piece = array_shift($path)) {
      if (!array_key_exists($piece, $node)) {
        if ($piece == '*') {
          // return the current item
          return $node;
        }
        else {
          // not found
          return $phrasePath;
        }
      }
      else {
        // move on to the next item
        $node = $node[$piece];
      }
    }
    return $node;
  }
  
  /**
   * Imports the provided $filePath into the dictionary
   * @param string $namespace Namespace to put into
   * @param string $filePath Dictionary file
   * @return boolean
   */
  private static function import($namespace, $filePath) {
    if (file_exists($filePath)) {
      include $filePath;
      if (isset($lang)) {
        self::$dictionary[$namespace] = $lang;
        return true;
      }
    }
    return false;
  }

}