<?php
/**
 * Manages the routing of the application
 */
class Router {

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Path to the controllers directory
   * @var string
   */
  private static $controllersPath;

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Initializes the router with the environment settings
   * @param string $controllersPath Path to the controllers directory
   */
  public static function init($controllersPath) {
    self::$controllersPath = $controllersPath;
  }
  
  /**
   * Routes the application according to the provided request
   * @param Request $request The action request
   * @return OutputInterface Controller's output
   */
  public static function route(Request $request) {
    if (!$request->getController()) {
      // page introuvable
      throw new UserException(Lang::get('foundation.CONTROLLER_NOT_FOUND', ''), UserException::NOT_FOUND);
    }

    $controllerName = self::lookup($request->getController());
    
    $controller = new $controllerName($request);

    // dispatcher la requête
    return $controller->_dispatch($request);
  }
  
  /**
   * Faire appel à une action d'un controlleur en particulier
   * @param string $controllerBaseName Nom de base du controlleur
   * @param string $action Nom de l'action
   * @param Request $request La requête d'origine
   * @return OutputInterface Sortie du controlleur
   */
  public static function invoke($controllerBaseName, $action, $request) {
    $controllerName = self::lookup($controllerBaseName);
    
    $controller = new $controllerName($request);
    
    // dispatcher l'action souhaitée
    return $controller->_dispatch($request, $action);
  }
  
  /**
   * Faire une rédirection HTTP vers un controlleur en particulier
   * @param string $controllerBaseName Nom de base du controlleur
   * @param string $action [optionnel] Nom de l'action
   * @param array $params [optionnel] Paramètres GET à envoyer
   * @return HeaderResponse
   */
  public static function redirect($controllerBaseName = '', $action = '', $params = null) {
    $path = '';
    if ($controllerBaseName != '')
      $path .= "/$controllerBaseName";
    if ($action != '')
      $path .= "/$action";
    if ($params)
      $path .= '?'.http_build_query((array)$params);
    return new HeaderResponse("Location: ".Application::getInstance()->getBaseUrl().$path);
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Cherche le controlleur et retourne le nom de la classe
   * @param string $controllerBaseName Le nom de base du controlleur
   * @return string Nom de la classe du controlleur
   */
  private static function lookup($controllerBaseName) {
    $controllerName = ucfirst($controllerBaseName) . 'Controller';
    $file = self::$controllersPath . '/' . $controllerName . '.php';
    if (!file_exists($file))
      throw new UserException(Lang::get('foundation.CONTROLLER_NOT_FOUND', $controllerBaseName), UserException::NOT_FOUND);

    require $file;

    return $controllerName;
  }

}