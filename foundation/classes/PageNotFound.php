<?php

class PageNotFound {

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  private $message;
  
  private $title;

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Constructor
   * @param string [$message=null] Message to show. If null, defaults to lang.foundation.PAGE_NOT_FOUND
   * @param string [$title=null] Page title. If null, defaults to lang.PAGE_NOT_FOUND_TEXT
   */
  public function __construct($message = null, $title = null) {
    $this->message = $message;
    $this->title = $title;
  }
  
  /**
   * Echos the page output to stream
   */
  public function send() {
    header('HTTP/1.0 404 Not Found');
    
    echo $this->getHtml();
  }
  
  /**
   * Returns the HTML for this component
   * @return string
   */
  public function getHtml() {
    $tplString = Parser::getTemplate('notfound', true)
      ?: '<!DOCTYPE html><html><head><meta charset="utf-8"><title>{%lang.$.generic.PAGE_NOT_FOUND%}</title></head><body><h1>{%TITLE%}</h1><p>{%MESSAGE%}</p></body></html>';
    
    return Parser::parseString($tplString, [
      "TITLE"   => ifNotNull($this->title, Lang::get('foundation.PAGE_NOT_FOUND')),
      "MESSAGE" => ifNotNull($this->message, Lang::get('foundation.PAGE_NOT_FOUND_TEXT'))
    ]);
  }
  
  /**
   * Returns a Response object for this components
   * @return \NotFoundResponse
   */
  public function getResponse() {
    return new NotFoundResponse(self::getHtml());
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////


}
