<?php
/**
 * Application MultiSearch
 */
class Application extends AbstractApplication {
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Invoked after constructing the application object
   */
  protected function afterConstruct() {
    // composer autoload
    require($this->getAppPath()."/vendor/autoload.php");
    
    // activate query debugging
    if (Configuration::isDev()) {
      $debugQueries = ifExists($_GET, 'queries');
      //$debugQueries = 1;
      if ($debugQueries)/* || isset($_COOKIE['XDEBUG_SESSION'])*/
        Database::debugQueries((int)$debugQueries);
    }
  }

}