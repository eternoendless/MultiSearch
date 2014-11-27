<?php
/**
 * Manages a session 
 * 
 * @package Foundation\Session
 */
abstract class BasicSessionService {
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Toggles debug mode
   * @var int|FALSE
   */
  protected static $DEBUG = false;
  
  /**
   * Cookie name
   * @var string
   */
  protected static $cookieName;
  
  /**
   * Private key for the cookie signature
   * @var string
   */
  protected static $cookieSignatureSecret = '';
  
  /**
   * Fields to hibernate in the cookie (you can't put everything in due to the 4 KB limit)
   * @var array 
   */
  protected static $hibernableFields = array();
  
  /**
   * Session information
   * @var array
   */
  protected static $sessionData;
  
  /**
   * Session name (use it to avoid data collision when using multiple sessions)
   * @var string
   */
  protected static $sessionName;
  
  /**
   * If true, use a cookie (or other resource) to preserve information across sessions
   * @var bool [default: true]
   */
  protected static $hibernate = true;

  /**
   * If true, keep track of session state when hibernating and compare when rehydrating.
   * While it's normally useful (and needed) for session persistence, in a multi-server environment,
   * it also prevents the session from going out of sync across servers.
   * @var bool [default: true]
   */
  protected static $sync = true;
  
  /**
   * Name for the digest hash used to sync the session with the cookie
   * @var string
   */
  protected static $syncDigestName = '@d';
  
  /**
   * Indicates whether the session should last across browser sessions
   * @var bool 
   */
  protected static $syncPersist = false;

  /**
   * Name of the flag used to indicate that the session should last across browser sessions
   * @var string 
   */
  protected static $syncPersistFlagName = '@persist';
  
  protected static $started = false;


  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  #----------------------------------------------------------------------------
  /**
   * Initializes the session
   */
  public static function start() {
    if (static::$started)
      throw new Exception("Cannot start ".  get_called_class() ." because it has already been started");
      
    CookieService::$signatureSecret = static::$cookieSignatureSecret;
    
    session_start();
    
    if (static::$sessionName)
      static::$sessionData =& $_SESSION[static::$sessionName];
    else
      static::$sessionData =& $_SESSION;
    
    if (!static::restoreSession())
      static::logoff();
    
    static::$started = true;
  }
  
  #----------------------------------------------------------------------------
  /**
   * Returns TRUE if the session has no data
   * @return boolean
   */
  public static function isEmpty() {
    return static::$sessionData == null;
  }

  #----------------------------------------------------------------------------
  /**
   * Replaces the session data with $data
   * @param array $data 
   */
  public static function setData($data) {
    static::$sessionData = $data;
  }
  
  #----------------------------------------------------------------------------
  /**
   * Adds (merges) $data into the session
   * @param array $data 
   * @param boolean $recursive TRUE to merge recursively [default: TRUE]
   */
  public static function addData($data, $recursive = true) {
    if (is_array(static::$sessionData)) {
      static::$sessionData = ($recursive)?
        array_replace_recursive(static::$sessionData, $data) : array_replace(static::$sessionData, $data);
    }
    else {
      static::$sessionData = $data;
    }
  }
  
  #----------------------------------------------------------------------------
  /**
   * Returns the requested session data.
   * @param string $path Dot-separated data path. Example: 'path.to.data' => static::$sessionData['path']['to']['data']
   * @return mixed The requested data, or NULL if not found
   */
  public static function getData($path = null) {
    if (!is_array(static::$sessionData))
      return null;
    
    return array_get_path(static::$sessionData, $path);
  }
  
  #----------------------------------------------------------------------------
  /**
   * Ends the current session 
   */
  public static function logoff() {
    // keep user selected language if it exists
    /*$userLang = static::getData('lang');
    if ($userLang)
      static::setData(array('lang' => $userLang));
    else*/
    static::$sessionData = null;
    if (static::$sessionName)
      $_SESSION[static::$sessionName] = null;
    else
      $_SESSION = null;
  }
  
  #----------------------------------------------------------------------------
  /**
   * Configures whether the session should last across browser sessions or not
   * @param bool $toggle TRUE to persist, FALSE to end the session when the browser session ends
   */
  public static function setPersistent($toggle) {
    static::$syncPersist = (bool)$toggle;
  }
  
  #----------------------------------------------------------------------------
  /**
   * Hybernates all hibernable fields to the cookie
   * @return bool
   */
  public static function hibernate() {
    if (static::$DEBUG === 2) {
      Logger::debug("Session contents");
      Logger::debug(static::getData());
    }
      
    if (static::$hibernate) {
      $hibernationData = static::getHibernationData();
      return static::hibernateSessionData($hibernationData);
    }
    return true;
  }
  
  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  #----------------------------------------------------------------------------
  /**
   * Retuns the data to hibernate to a persistent location
   * @return mixed
   */
  protected static function getHibernationData() {
    $dataToSave = array();
    
    if (is_array(static::$sessionData) && !empty(static::$sessionData)) {
      // extract data to hibernate
      if (!empty(static::$hibernableFields)) {
        $dataToSave = array_extract_recursive(static::$sessionData, static::$hibernableFields);
        if (static::$DEBUG === 2)
          Logger::debug($dataToSave, 'Session data to hibernate');
      }
      
      if (static::$sync && !empty($dataToSave)) {
        // add sync hash
        $dataToSave[static::$syncDigestName] = static::createDigest();
        
        if (static::$syncPersist) {
          // add persistence flag
          $dataToSave[static::$syncPersist] = 1;
        }
      }
      
    }
    
    return $dataToSave;
  }

  #----------------------------------------------------------------------------
  /**
   * Saves the provided data to a persistent location (e.g. a cookie).
   * Override this method to save to a different resource.
   * @param mixed $data
   * @return boolean
   */
  protected static function hibernateSessionData($data) {
    if (!empty($data)) {
      if (static::$DEBUG)
        Logger::debug('Saving hibernation data');
      
      return static::saveHibernationData($data);
    }
    elseif (static::$cookieName && isset($_COOKIE[static::$cookieName])) {
      if (static::$DEBUG)
        Logger::debug("Deleting hibernated data");
      
      $cfg = Configuration::get('@foundation.session');
      
      $path = $cfg['cookiePath'];
      $domain = $cfg['cookieDomain'];
      
      // delete the cookie
      CookieService::destroy(static::$cookieName, $path, $domain);
    }
    return true;
  }
  
  #----------------------------------------------------------------------------
  /**
   * Wakes up the session from its hibernated state.
   * Override this method to load hibernated data from another resource.
   * @return boolean
   */
  protected static function restoreSession() {
    if (static::$hibernate) {
      $data = static::loadHibernatedData();
      
      if (static::$DEBUG)
        Logger::debug("Restoring session from hibernation");
      
      // if there's no data in the cookie, or failed
      if (!$data || empty($data)) {
        if (static::$DEBUG)
          Logger::debug("Could not restore session because there was no data or cookie was not available");
        return false;
      }
      
      if (static::$DEBUG === 2)
        Logger::debug($data, "hibernated data loaded");
      
      // hydrate the session using this data
      static::syncSessionData($data);
    }
    
    // check for persistent flag
    static::$syncPersist = (isset(static::$sessionData[static::$syncPersistFlagName]));
    
    // validate & expand
    $valid = static::validateData();
    
    if (!$valid && static::$DEBUG)
      Logger::debug("Could not restore session because of data validation failure");
    
    return $valid;
  }

  #----------------------------------------------------------------------------
  /**
   * Syncs the session using the data from the hibernated resource (eg. cookie).
   * Override this method to for further data hydration.
   * @param mixed $data Extracted data
   * @return bool
   */
  protected static function syncSessionData($data) {
    // if digests match, there's no need to update the session
    if (!static::$sync || !static::checkDigest($data[static::$syncDigestName])) {
      // session no longer up to date

      // overwrite the session with the retrieved data
      static::$sessionData = $data;
    }
    return true;
  }
  
  #----------------------------------------------------------------------------
  /**
   * Template metod: check if the session data is still valid (eg. user still exists, etc) and expand it if needed (fill data)
   * @return boolean
   */
  protected static function validateData() {
    return true;
  }

  #----------------------------------------------------------------------------
  /**
   * Checks the session status hash against the one from the hibernated resource (eg. cookie)
   * @param string $challenge Hash from the hibernated resource
   * @return bool TRUE if they are equal, FALSE if they are not
   */
  protected static function checkDigest($challenge) {
    $dn = static::getData(static::$syncDigestName);
    return ($dn && $dn == $challenge);
  }

  #----------------------------------------------------------------------------
  /**
   * Returns a digest based on the session's data
   * @return string
   */
  protected static function createDigest() {
    return md5(serialize(static::getData()));
  }

  #----------------------------------------------------------------------------
  /**
   * Saves $data to the hibernation resource (eg. cookie)
   * @param array $data
   * @return boolean
   */
  protected static function saveHibernationData($data) {
    if (static::$cookieName) {
      $cfg = Configuration::get('@foundation.session');
      
      // max cookie duration [default: 90 days] or session length
      $expiration = (static::$syncPersist)?
        time()+$cfg['maxCookieDuration'] : 0;
      
      $path = $cfg['cookiePath'];
      $domain = $cfg['cookieDomain'];
      
      return CookieService::set(static::$cookieName, $data, $expiration, $path, $domain);
    }
    return false;
  }

  #----------------------------------------------------------------------------
  /**
   * Tries loading the cookie data inside the session
   * @return bool 
   */
  protected static function loadHibernatedData() {
    if (static::$cookieName)
      return CookieService::get(static::$cookieName);
  }

}