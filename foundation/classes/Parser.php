<?php
/**
 * Traitement de templates
 */
class Parser {

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Répertoire des templates
   * @var string
   */
  private static $tplDir = '';
  
  /**
   * Cache de templates préparés
   * @var array
   */
  private static $tplCache = array();

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Initialisation
   * @param string $tplDir Répertoire des templates
   */
  public static function init($tplDir) {
    self::$tplDir = $tplDir;
  }
  
  /**
   * Cherche dans le template $tplName les identifiants donnés comme clé de $data et les remplace par les valeurs associés
   * @param string $tplName Nom du template
   * @param array $data Array associatif sous la forme 'IDENTIFIANT' => 'valeur de remplacement'
   * @return string Le template processé
   * @throws Exception
   */
  public static function parse($tplName, array $data = array()) {
    $tplContents = self::getPreparedTemplate($tplName);
    return self::parseString($tplContents, $data);
  }
  
  /**
   * Cherche dans $tplString les identifiants donnés comme clé de $data et les remplace par les valeurs associés
   * @param type $tplString Texte sur lequel travailler
   * @param array $data Array associatif sous la forme 'IDENTIFIANT' => 'valeur de remplacement'
   * @return string Le texte processé
   */
  public static function parseString($tplString, array $data = array()) {
    $keys = $values = array();
    foreach ($data as $k => $v) {
      $keys[] = '{%'.$k.'%}';
      $values[] = $v;
    }
    
    return str_replace($keys, $values, $tplString);
  }
  
  /**
   * Checks if a template exists
   * @param string $tplName Template name
   * @return bool TRUE if the template exists, FALSE otherwise
   */
  public static function templateExists($tplName) {
    return (self::getPreparedTemplate($tplName, true) !== null);
  }
  
  /**
   * Returns the content of a template
   * @param string $tplName Template name
   * @param bool $nullOnNotFound [optional] Return null if the template cannot be found instead of raising an Exception (default: false)
   * @return string|null The contents of the template, NULL if the template was not found and $nullOnNotFound is TRUE
   */
  public static function getTemplate($tplName, $nullOnNotFound = false) {
    return self::getPreparedTemplate($tplName, $nullOnNotFound);
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Retourne TRUE si le template existe dans le cache
   * @param string $tplName Nom du template
   * @return type 
   */
  private static function isInCache($tplName) {
    $langCode = Lang::getCurrentLangCode();
    
    return isset(self::$tplCache[$tplName][$langCode]);
  }
  
  /**
   * Extrait le contenu du template depuis le cache
   * @param string $tplName Nom du template
   * @return mixed Le contenu du template si existe ou NULL sinon
   */
  private static function loadFromCache($tplName) {
    $langCode = Lang::getCurrentLangCode();
    
    return (self::isInCache($tplName, $langCode))?
      self::$tplCache[$tplName][$langCode] : null;
  }
  
  /**
   * Stocke le template preparé dans le cache
   * @param string $tplName Le nom du template
   * @param string $tplContents Contenu preparé du template
   */
  private static function saveToCache($tplName, $tplContents) {
    $langCode = Lang::getCurrentLangCode();
    
    self::$tplCache[$tplName][$langCode] = $tplContents;
  }
  
  /**
   * Loads a template from a file
   * @param string $tplName Template name
   * @param bool [optional] Return null if the template cannot be found instead of raising an Exception (default: false)
   * @return string The contents of the template file
   */
  private static function loadFromFile($tplName, $nullOnNotFound = false) {
    $tplPath = self::$tplDir . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $tplName) . '.tpl';
    
    if (!file_exists($tplPath)) {
      if ($nullOnNotFound)
        return null;
      
      throw new Exception(Lang::get('foundation.FILE_X_NOT_FOUND', $tplPath));
    }
    
    return file_get_contents($tplPath);
  }
  
  /**
   * Checks and prepares the template
   * 
   * @param string $tplName Template name
   * @param bool [optional] Return null if the template cannot be found instead of raising an Exception (default: false)
   * @return string The template contents after preparation
   */
  private static function getPreparedTemplate($tplName, $nullOnNotFound = false) {
    // check if the template already exists in the cache
    if (self::isInCache($tplName)) {
      // return the prepared template in cache
      return self::loadFromCache($tplName);
    }
    
    // load the contents of the template
    $tplContents = self::loadFromFile($tplName, $nullOnNotFound);
    if ($nullOnNotFound && $tplContents === null)
      return null;
    
    $tplContents = self::prepareTemplate($tplContents);
    
    // save in cache
    self::saveToCache($tplName, $tplContents);
    
    return $tplContents;
  }
  
  /**
   * Prépare le template en remplaçant les phrases de i18n
   * @param string $tplContents Contenu du template
   * @return string Le code préparé
   */
  private static function prepareTemplate($tplContents) {
    $data = array();
    
    // vérifier s'il est nécessaire de traduire des tokens d'i18n
    $langTokens = self::extractSpecificTokens('lang', $tplContents);
    
    if (is_array($langTokens)) {
      // construire le vecteur de remplacement de tokens vers les valeurs correspondantes
      foreach ($langTokens as $token) {
        // supprimer la partie 'lang.' du token
        $phrase = substr($token,5);
        $data[$token] = Lang::get($phrase);
      }
    }
    
    // s'il y a quelque chose à traduire...
    if (!empty($data)) {
      // effectuer le remplacement
      $tplContents = self::parseString($tplContents, $data);
    }
    
    return $tplContents;
  }
  
  /**
   * Retourne la liste de tokens que commencent par $tokenPrefix dans $tplContents
   * @param string $tokenPrefix Le prefixe à recherche
   * @param string $tplContents Le sujet de la recherche
   * @return array|false Vecteur de tokens trouvés ou FALSE
   */
  private static function extractSpecificTokens($tokenPrefix, $tplContents) {
    $regex = "/{%({$tokenPrefix}.[\w\.$]+)%}/";
    $matches = array();
    if (preg_match_all($regex,$tplContents,$matches) > 0) {
      return array_unique($matches[1]);
    }
    else return false;
  }
  
  /**
   * Classe statique
   */
  private function __construct() {}
  
}