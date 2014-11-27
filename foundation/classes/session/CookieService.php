<?php

/**
 * Simple cookie managing
 * 
 * @author Pablo Borowicz
 */
class CookieService {
  
  const SIGNATURE_SEPARATOR = ':';
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  public static $signatureSecret;

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////

  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Sets $data as a cookie
   * @param string $cookieName The name of the cookie
   * @param mixed $data Data to put inside the cookie
   * @param int $expiration [optional] The time the cookie expires. Timestamp or zero (for session-long) [default: 0]
   * @param string $path [optional] The path on the server in which the cookie will be available on. [default: null]
   * @param string $domain [optional] The domain that the cookie is available to. [default: null]
   * @param bool $secure [optional] Indicates that the cookie should be sent by HTTPS. [default: null]
   * @param bool $httpOnly [optional] When TRUE, the cookie will only be accessible through HTTP [default: TRUE]
   * @return bool
   */
  public static function set($cookieName, $data, $expiration = 0, $path = null, $domain = null, $secure = false, $httpOnly = true) {
    $packedData = self::packData($data);
    
    return setcookie($cookieName, $packedData, $expiration, $path, $domain, $secure, $httpOnly);
  }
  
  /**
   * Returns the information stored in $cookieName after checking for its integrity
   * @param string $cookieName
   * @return mixed The stored data or FALSE
   */
  public static function get($cookieName) {
    if (isset($_COOKIE[$cookieName])) {
      $cookieData = $_COOKIE[$cookieName];
      
      if (!empty($cookieData)) {
        return self::unpackData($cookieData);
      }
    }
    return false;
  }
  
  /**
   * Unsets a cookie
   * @param string $cookieName
   */
  public static function destroy($cookieName, $path = null, $domain = null, $secure = false, $httpOnly = true) {
    setcookie($cookieName, false, 1, $path, $domain, $secure, $httpOnly);
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Returns TRUE if the data inside $cookieData has been successfully validated using $signature
   * @param string $cookieData
   * @param string $signature
   * @return bool 
   */
  protected static function validateData($cookieData, $signature) {
    return (sha1($cookieData.static::$signatureSecret) == $signature);
  }
  
  /**
   * Packs data for storing into a cookie
   * @param mixed $data
   * @return string 
   */
  private static function packData($data) {
    $cookieData = json_encode($data);
    $signature = sha1($cookieData.self::$signatureSecret);
    return base64_encode($cookieData).self::SIGNATURE_SEPARATOR.$signature;
  }
  
  /**
   * Unpacks data from a cookie
   * @param string $data
   * @return mixed
   */
  private static function unpackData($data) {
    $debug = false;
    
    $cookieRawData = explode(self::SIGNATURE_SEPARATOR, $data);
    if (count($cookieRawData == 2)) {
      $data = base64_decode($cookieRawData[0]);
      $signature = $cookieRawData[1];
      if (self::validateData($data, $signature)) {
        $decodedData = json_decode($data, true);
        if ($decodedData)
          return $decodedData;
      }
      elseif ($debug) {
        Logger::debug('Bad signature');
      }
    }
    elseif ($debug) {
      Logger::debug('Bad cookie');
    }
    return false;
  }

}