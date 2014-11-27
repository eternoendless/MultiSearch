<?php
/**
 * Allows subscribing to events
 * 
 * @package Foundation\Core
 */
class EventManager {

  const E_EMPTY_EVENT_NAME = "An event name cannot not be empty";
  
  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  private static $subscriptions = [];

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  /**
   * Adds a subscription to an event. The callback must accept a single parameter.
   * @param string $eventName The name of the event to subcribe to
   * @param callable $callback The callback to subscribe
   * @throws InvalidArgumentException
   */
  public static function subscribe($eventName, callable $callback) {
    if (empty($eventName))
      throw new InvalidArgumentException(self::E_EMPTY_EVENT_NAME);
    
    self::$subscriptions[$eventName][] = $callback;
  }
  
  /**
   * Unsubscribes a callback from an event
   * @param string $eventName The name of the event
   * @param callable $callback The callback to unsubscribe
   * @throws InvalidArgumentException
   */
  public static function unsubscribe($eventName, callable $callback) {
    if (empty($eventName))
      throw new InvalidArgumentException(self::E_EMPTY_EVENT_NAME);
    
    $key = array_search($callback, self::$subscriptions[$eventName], true);
    if ($key !== false) {
      unset(self::$subscriptions[$eventName][$key]);
      if (empty(self::$subscriptions[$eventName]))
        unset(self::$subscriptions[$eventName]);
    }
  }
  
  /**
   * Dispatches an event to all subscribers
   * @param string $eventName The name of the event to dispatch
   * @param mixed $eventArguments The event arguments to pass on to subscribers
   * @throws InvalidArgumentException
   */
  public static function dispatch($eventName, $eventArguments = null) {
    if (empty($eventName))
      throw new InvalidArgumentException(self::E_EMPTY_EVENT_NAME);
    
    if (isset(self::$subscriptions[$eventName])) {
      foreach (self::$subscriptions[$eventName] as $callback) {
        call_user_func($callback, $eventArguments);
      }
    }
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////


}