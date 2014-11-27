<?php
/**
 * Simplfies access to a Twig instance
 * 
 * @package MultiSearch
 * @author Pablo Borowicz
 */
trait TwigTemplate  {

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
   * Returns a Twig instance
   * @return \Twig_Environment
   */
  private function _getTwig() {
    $app = Application::getInstance();
    $debug = Configuration::isDev();
    
    Twig_Autoloader::register();
    
    $loader = new Twig_Loader_Filesystem($app->getContextPath().'/tpl');
    
    $twig = new Twig_Environment($loader, array(
      'debug' => $debug,
      'cache' => $app->getCachePath()
    ));
    
    if ($debug) {
      $twig->addExtension(new Twig_Extension_Debug());
    }
    
    return $twig;
  }

}