<?php
/**
 * Application configuration
 * 
 * @package MutliSearch
 * @author Pablo Borowicz
 */
class Configuration extends AbstractConfiguration {

  protected static function getInitialSettings() {
    
    static::$envHosts['prod'] = 'test-pabloboro.rhcloud.com';
    
    $appPath = Application::getInstance()->getAppPath();
    
    $cfg = [];
    
    // Configuration pour DEV -------------------------------------------------
    $cfg['dev'] = [
      '@foundation' => [
        'logPath' => $appPath . '/../log/',
      ]
    ];
      
    // defaults (fallback) ----------------------------------------------------
    $cfg['default'] = [
      '@foundation' => [
        'i18n' => [
          // langue par défaut
          'defaultLanguage' => 'fr',
          'supportedLanguages' => ['en', 'fr', 'es']
        ],
        'session' => [
          'enabled' => false
        ],
        // path du répertoire des logs
        'logPath'        => '/var/log/',
        'errorLogPrefix' => 'error-'
      ]
      
    ];
    
    return $cfg;
  }
  
}