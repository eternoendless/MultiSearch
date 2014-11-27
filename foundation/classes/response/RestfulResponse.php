<?php

class RestfulResponse extends HttpResponse {
  
  const FORMAT_JSON = 'json';
  const FORMAT_HTML = 'html';
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  public static $defaultFormat = self::FORMAT_JSON;
  
  public $success,
         $data,
         $message,
         $errors,
         $tid,
         $trace,
         $extraParams;

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Output format (JSON, HTML, etc..)
   * @var string
   */
  private $outputFormat = null;
  private $outputFlags = null;

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Constructor
   * @param array|Exception $params { success: bool, data: mixed, message: string, errors: array }
   * @param array $headers [optional]
   * @param string $outputFormat [optional]
   */
  public function __construct($params = array(), $headers = array(), $outputFormat = null, $outputFlags = null) {
    if ($outputFormat === null)
      $this->outputFormat = self::$defaultFormat;
    if ($outputFlags !== null)
      $this->outputFlags = $outputFlags;
    
    // if we get an Exception instead of params
    if ($params instanceof Exception) {
      // get headers from exception if it's an UserException with non-null headers
      if ($params instanceof UserException && $params->getHeaders())
        $headers = $params->getHeaders();
      elseif (empty($headers))
        $headers = self::STATUS_SERVER_ERROR;
      
      $msg = '';
      
      // if it's aun uncaught exception and not a purposedly thrown UserException,
      // add the exception type to the error message for informational purposes
      if (!($params instanceof UserException))
        $msg .= '['.get_class($params).'] ';
      
      // append exception message
      $msg .= $params->getMessage();
      
      // if in a development environment, throw in some debugging information
      if (Configuration::isDev() && !($params instanceof UserException))
        $msg .= ' – '.$params->getFile(). ' @ line '.$params->getLine();
      
      $this->init(array(
        'success' => false,
        'message' => $msg
      ), $headers);
    }
    else {
      $this->init($params, $headers);
    }
  }
  
  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Initializes the response
   * @param array $params
   * @param arary $headers
   */
  protected function init($params, $headers = array()) {
    $this->success  = ifExists($params,'success',true);
    $this->data     = ifNotEmpty(ifExists($params,'data'));
    $this->message  = ifNotEmpty(ifExists($params,'message'));
    $this->errors   = ifNotEmpty(ifExists($params,'errors'));
    
    $headers = (array)$headers;
    
    if (!Configuration::isDev()) {
      switch($this->outputFormat) {
        case self::FORMAT_JSON:
          $headers[] = self::CONTENT_JSON; break;
        case self::FORMAT_HTML:
          $headers[] = self::CONTENT_HTML; break;
      }
    }
    
    $this->headers = $headers;
    
    foreach (array('success','data','message','errors') as $paramName) {
      if (array_key_exists($paramName, $params))
        unset($params[$paramName]);
    }
    if (!empty($params)) {
      $this->extraParams = $params;
    }
  }
  
  /**
   * Returns the processed contents, ready to be sent to the stream
   * @return string
   */
  protected function getProcessedContent() {
    $content = array(
      'success'   => $this->success,
      'data'      => $this->data
    );
    
    foreach (array('data','message','errors') as $paramName) {
      if (!is_null($this->{$paramName}))
        $content[$paramName] = $this->{$paramName};
    }
    
    if (is_array($this->extraParams))
      $content = array_merge($content, $this->extraParams);
    
    $this->content = $content;
    
    if ($this->outputFormat == 'json') {
      if ($this->outputFlags && defined('JSON_NUMERIC_CHECK'))
        return json_encode($this->content, $this->outputFlags);
      elseif ($this->outputFlags == 'JSON_NUMERIC_CHECK')
        return json_encode($this->jsonNumericCheck($this->content));
      
      return json_encode($this->content);
    }
    else
      throw new Exception('Unsupported output format');
  }

  /**
   * Transforms string values to numeric values when possible
   * @param array $array
   * @return array
   */
  private function jsonNumericCheck($array) {
    foreach ($array as &$item) {
      if (is_array($item))
        $item = $this->jsonNumericCheck($item);
      elseif (is_numeric($item))
        $item = (int)$item;
    }
    return $array;
  }

}