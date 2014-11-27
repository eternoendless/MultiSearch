<?php
/**
 * Class autoloader
 * 
 * @package FoundationPHP
 * @author Pablo Borowicz
 */
class ClassLoader {
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Class lookup map.
   * @var array
   */
  private static $map = array();
  
  /**
   * Indicates whether the class lookup map has benn modified since the last stored version or not.
   * @var bool
   */
  private static $mapModified = false;
  
  /**
   * Directories to crawl for classes, sorted by priority
   * @var array
   */
  private static $lookupDirectories = array();
  
  /**
   * Cache file name
   * @var string
   */
  private static $cacheFileName = 'class_map.php';

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Initializes the ClassLoader
   * @param array $lookupDirectories Directories to crawl for classes, sorted by priority
   */
  public static function init($lookupDirectories) {
    self::$lookupDirectories = $lookupDirectories;
    
    self::loadCache();
    self::register();
  }
  
  /**
   * Adds a path to the lookup directories list.
   * @param string $path
   * @param int $position [optional] If provided, insert at the selected position within the list
   * (use negative numbers to start from the bottom of the list). If NULL or not numeric, inserts at the bottom of the list.
   */
  public static function addLookupPath($path, $position = null) {
    if ($position === null || !is_numeric($position))
      self::$lookupDirectories[] = $path;
    else
      array_splice(self::$lookupDirectories, $position, 0, $path);
  }
  
  /**
   * Loads the given class or interface.
   *
   * @param string $className The name of the class
   */
  public static function loadClass($className) {
    if (isset(self::$map[$className]) && file_exists(self::$map[$className])) {
      require self::$map[$className];
      return true;
    }
    
    $path = self::findClass($className);
    if ($path !== false) {
      self::$map[$className] = $path;
      self::$mapModified = true;
      return self::loadClass($className);
    }
    
    return false;
  }
  
  /**
   * Saves the class lookup map to a cache file.
   * @param $cachePath Path to the cache directory
   */
  public static function hibernate($cachePath) {
    if (!empty($cachePath))
      self::saveCache($cachePath);
  }
  
  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Registers the autoloader.
   *
   * @param Boolean $prepend Whether to prepend the autoloader or not
   */
  private static function register($prepend = false) {
    spl_autoload_register(get_called_class().'::loadClass', true, $prepend);
  }
  
  /**
   * Finds a class in the dictionary
   * @param string $className
   * @return string|bool Full file path if it was found, FALSE otherwise
   */
  private static function findClass($className) {
    foreach (self::$lookupDirectories as $directory) {
      if (!empty($directory) && file_exists($directory)) {
        $path = self::findFile(strtolower($className).'.php', $directory);
        //echo $path;
        if ($path !== false)
          return $path;
      }
    }
    return false;
  }
  
  /**
   * Finds a file recusively within a directory
   * @param string $fileName File name (including extension)
   * @param string $directoryPath Directory path
   * @return string|bool Full file path if it was found, FALSE otherwise
   */
  private static function findFile($fileName, $directoryPath) {
    $directory = dir($directoryPath);
    while ($entry = $directory->read()) {
      if ($entry != "." && $entry != "..") {
        $current = $directoryPath.DIRECTORY_SEPARATOR.$entry;
        //echo "$entry == $fileName<br>";
        if (strtolower($entry) == $fileName) {
          $directory->close();
          return $current;
        }
        
        if (is_dir($current)) {
          $path = self::findFile($fileName, $current);
          if ($path) {
            $directory->close();
            return $path;
          }
        }
      }
    }
    $directory->close();
    return false;
  }
  
  /**
   * Loads the dictionary from the cache file
   */
  private static function loadCache() {
    $path = self::getCacheLocation() . '/' . self::$cacheFileName;
    if (file_exists($path)) {
      $unserialized = unserialize(file_get_contents($path));
      if (is_array($unserialized))
        self::$map = $unserialized;
    }
  }
  
  /**
   * Saves the dictionary to the cache file
   */
  private static function saveCache() {
    $cachePath = self::getCacheLocation();
    if (self::$mapModified) {
      if (!file_exists($cachePath) && !@mkdir($cachePath, 0777, true))
        throw new Exception("Unable to create cache directory in ".$cachePath);
      
      $tmp = tempnam($cachePath, 'map');
      if (file_put_contents($tmp, serialize(self::$map)) === false)
        throw new Exception("Could not write class map, check permissions for ". dirname(realpath($tmp)));
      if (!rename($tmp, $cachePath . '/' . self::$cacheFileName))
        throw new Exception("Could not update class map, check write permissions for ". realpath($cachePath));
    }
  }
  
  /**
   * Return the full path to the cache directory
   * @return string
   */
  private static function getCacheLocation() {
    return Application::getInstance()->getCachePath();
  }
  
}