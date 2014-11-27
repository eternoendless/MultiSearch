<?php
/**
 * Provides logging functionality
 * 
 * @package Foundation
 */
class Logger {
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Prints a debug message
   * @param mixed $var Variable to dump
   * @param string $title [optional] Title (autofilled with last method name if empty)
   * @param bool $html [optional] TRUE to wrap the dump in &lt;pre> tags, default TRUE (FALSE if XMLHttpRequest headers are found)
   * @param int $stackTrace [optional] if TRUE, show a full stack trace (default: FALSE)
   */
  public static function debug($var, $title = null, $html = null, $stackTrace = false) {
    if ($html === null)
      $html = !(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
    
    if ($title === null)// && function_exists('xdebug_call_class'))
      $title = self::getStackTrace(2,1);//sprintf("%s::%s @ line %d", xdebug_call_class(), xdebug_call_function(), xdebug_call_line());

    $s = "/*\n".(($html)? '<pre>' : '');
    //$s .= self::getBacktrace();
    if (!empty($title))
      $s .= "--- $title ---\n";
    $s .= print_r($var,true)."\n";
    if ($stackTrace)
      $s .= "\nStack trace:\n".self::getStackTrace(2);
    $s .= (($html)? '</pre>' : '')."*/";
    
    echo $s;
  }
  
  /**
   * Logs an error
   * @param string|Exception $message
   */
  public static function log($message){
    //var_dump($message->getTrace());
    //error_log()
    //echo "<pre>".self::traceAsString($message->getTrace(), true);
    
    $trace = ($message instanceof Exception)?
      self::traceAsString($message->getTrace(), true)
      : self::getStackTrace(1);
    
    $msg = sprintf(
      "\n============================================================\n"
      . "Date : %s\n"
      . "Exception de type [%s]\n"
      . "Message : %s\n"
      . "Fichier : %s @ ligne %s\n"
      . "------------------------------------------------------------\n\n"
      . "Stack dump :\n\n%s\n",
      date(DATE_RFC850),
      get_class($message),
      $message->getMessage(),
      $message->getFile(),
      $message->getLine(),
      $trace
    );
    $logPath = Configuration::ifExists('@foundation.logPath');
    if (is_dir($logPath)) {
      $logFile = $logPath.'error-'.date('Ymd').'.log';
      file_put_contents($logFile, $msg, FILE_APPEND);
    }
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Returns a string-formatted stack trace
   * @param int $start [optional] Offset start
   * @param int $count [optional] Number of items to return
   * @param bool $full [optional] If TRUE, show full information
   * @return string
   */
  private static function getStackTrace($start = null, $count = null, $full = false) {
    $trace = debug_backtrace($full, ($full || $count == null)? 0 : ($start+$count));
    
    if ($start > 0)
      $trace = array_slice($trace, $start, $count);
    
    return self::traceAsString($trace, $full);
  }
  
  /**
   * Beautifies a stack trace
   * @param array $trace Stack trace
   * @param bool $full [optional] If TRUE, returns more information (default: FALSE)
   * @return string
   */
  private static function traceAsString($trace, $full = false) {
    $list = array();
    foreach ($trace as $k => $item) {
      if ($full) {
        if (empty($item['args']))
          $args = '';
        else {
          $args = array_map('var_export', $item['args'], array_fill(0, count($item['args']), true));
          $args = implode(",\n", $args);
          $args = preg_replace("/^(.)/m", "  \$1", "\n$args\n");
        }
        $list[] = sprintf("#$k: %s @ line %s\n%s%s%s(%s)\n",
          ifExists($item, 'file', '[internal function]'),
          ifExists($item, 'line', '[unknown]'),
          ifExists($item, 'class'),
          ifExists($item, 'type'),
          ifExists($item, 'function'),
          $args
        );
      }
      else {
        $list[] = sprintf('%s%s%s | %s @ line %s',
          ifExists($item, 'class'),
          ifExists($item, 'type'),
          ifExists($item, 'function'),
          isset($item['file'])? basename($item['file']) : '[internal function]',
          ifExists($item, 'line', '[unknown]')
        );
      }
       //if ($full)
        //$list[] = $item['class'].$item['type'].$item['function'].' | '.basename($item['file']).' @ line '.$item['line'];
      //else
      //  $list
    }
    return implode("\n",$list);
  }
  
}