<?php

class UserException extends Exception {
  
  /**
   * Bad input from user
   */
  const BAD_PARAMS = 10400;
  /**
   * User is not logged
   */
  const NOT_LOGGED = 10401;
  /**
   * User is logged but can't access data
   */
  const NO_ACCESS = 10403;
  /**
   * Object not found
   */
  const NOT_FOUND = 10404;

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
   * Returns the corresponding headers for the error type
   * @return string|null
   */
  public function getHeaders() {
    switch ($this->getCode()) {
      case self::BAD_PARAMS:
        return HttpResponse::STATUS_BAD_REQUEST; break;
      case self::NOT_LOGGED:
        return HttpResponse::STATUS_UNAUTHORIZED; break;
      case self::NO_ACCESS:
        return HttpResponse::STATUS_FORBIDDEN; break;
      case self::NOT_FOUND:
        return HttpResponse::STATUS_NOT_FOUND; break;
    }
    return null;
  }
  
  public function __construct($message, $code = self::BAD_PARAMS, $previous = null) {
    parent::__construct($message, $code, $previous);
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////


}