<?php
/**
 * Provides a kind of versioning by means of file signatures.
 * 
 * Signatures are created using this simple formula: md5(modification_timestamp.file_size)
 * 
 * @package Foundation
 */
class Versioning {

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Watched files list
   * @var string[]
   */
  private static $watchedFiles = array();

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Configures the files to watch
   * @param string[] $files An array containing file paths.
   * Use named keys in order to retrieve the signatures afterwards.
   */
  public static function setWatchedFiles($files) {
    self::$watchedFiles = $files;
  }
  
  /**
   * Returns the signatures for all the watched files
   * @return string[] An array containing the signature for each file
   */
  public static function getSignatures() {
    $signatures = array(); 
    foreach (array_keys(self::$watchedFiles) as $identifier) {
      $signatures[$identifier] = self::getSignatureFor($identifier);
    }
    return $signatures;
  }
  
  /**
   * Returns the signature for a named file
   * @param string $identifier The identifier used when declaring the file
   * @return string The signature for that file
   */
  public static function getSignatureFor($identifier) {
    $signature = '';
    if (isset(self::$watchedFiles[$identifier])) {
      $path = self::$watchedFiles[$identifier];
      if (file_exists($path)) {
        $timestamp = filemtime($path);
        $size = filesize($path);
        $signature = md5($timestamp.$size);
      }
    }
    return $signature;
  }
      
  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////

  
  
}