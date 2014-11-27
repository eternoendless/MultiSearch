<?php
/**
 * Manages database connections
 * 
 * @package Foundation
 */
class Database {

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * @var array Connection cache
   */
  private static $connections = array();
  
  private static $debug = false;

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   *
   * @return PdoWrapper 
   */
  /**
   * Returns a connection to the database
   * @param string $db Database ID
   * @param bool $forceNew [optional] If TRUE, force a new connection
   * @return \PDOWrapper
   */
  public static function get($db = 'db', $forceNew = false) {
    if ($forceNew || !isset(self::$connections[$db])) {
      try {
        $conn = new PDOWrapper(
        'mysql:host='.Configuration::get("$db.host").';dbname='.Configuration::get("$db.db_name").';port='.Configuration::get("$db.port"),
        Configuration::get("$db.user"),
        Configuration::get("$db.pass"),
        array(
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
        ));
        $conn->setDebug(self::$debug);
        
        if ($forceNew)
          return $conn;
        
        self::$connections[$db] = $conn;
        
      }
      catch(Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo "Impossible de se connecter à la base de données :<br />"
          .$e->getMessage();
        die;
      }
    }
    return self::$connections[$db];
  }
  
  /**
   * Activates or deactivates debugging of queries
   * @param int $debugLevel
   */
  public static function debugQueries($debugLevel) {
    
    self::$debug = $debugLevel;
    
    /* @var $conn PDOWrapper */
    foreach (self::$connections as $conn) {
      $conn->setDebug($debugLevel);
    }
  }
  
  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Static class
   */
  private function __construct() {}
  
}