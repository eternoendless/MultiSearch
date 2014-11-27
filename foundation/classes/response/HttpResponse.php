<?php
/**
 * Simple HTTP response
 */
class HttpResponse implements ResponseInterface {

  const STATUS_CREATED = "HTTP/1.1 201 Created";
  const STATUS_ACCEPTED = "HTTP/1.1 202 Accepted";
  const STATUS_MOVED_PERMANENTLY = "HTTP/1.1 301 Moved Permanently";
  const STATUS_BAD_REQUEST = "HTTP/1.1 400 Bad Request";
  const STATUS_UNAUTHORIZED = "HTTP/1.1 401 Unauthorized";
  const STATUS_FORBIDDEN = "HTTP/1.1 403 Forbidden";
  const STATUS_NOT_FOUND = "HTTP/1.1 404 Not Found";
  const STATUS_SERVER_ERROR = "HTTP/1.1 500 Internal Server Error";
  
  const CONTENT_PLAIN_TEXT = "Content-Type: text/plain; charset=utf-8";
  const CONTENT_HTML = "Content-Type: text/html; charset=utf-8";
  const CONTENT_JSON = "Content-Type: application/json; charset=utf-8";
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  protected $headers = array();
  protected $content = null;

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * 
   * @param string $content [optional] Response contents
   * @param array|string $headers [optional] Response HTTP headers
   */
  public function __construct($content = null, $headers = null) {
    $this->content = $content;
    $this->headers = (array)$headers;
  }
  
  /**
   * Sends the response headers and content to stream
   */
  public function send() {
    
    // send headers
    foreach($this->headers as $header) {
      header($header);
    }
    
    // send content
    $processedContent = $this->getProcessedContent();
    if (!is_null($processedContent))
      echo $processedContent;
  }
  
  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Returns the processed contents, ready to be sent to the stream
   * @return string
   */
  protected function getProcessedContent() {
    return $this->content;
  }

}