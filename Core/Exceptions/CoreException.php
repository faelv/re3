<?php

/**
 * CoreException class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Exceptions;

/**
 * Base exception for Core namespace
 *
 * @package Core
 */
class CoreException extends \Exception {

  /**
   * Constructor.
   *
   * @param string $message  The exception message
   * @param int    $code     The exception code
   * @param object $previous An optional previous exception object.
   * @param mixed  ...$extra Any number of extra parameters of any type.
   */
  public function __construct(string $message = '', int $code = 0, \Throwable $previous = null, ...$extra) {
    parent::__construct($message, $code, $previous);
  }

  /**
   * Returns an instance of the specified class.
   *
   * @param string $class    Class name. If the class name starts with a \ (backslash), then it's considered a FQN,
   *                         otherwise it will be infered that the class belongs to the same namespace as the class in
   *                         which the create method was called, in this case a namespace will be automatically added to
   *                         the class parameter.
   * @param string $message  The exception message
   * @param int    $code     The exception code
   * @param object $previous An optional previous exception object.
   * @param mixed  ...$extra Any number of extra parameters of any type.
   *
   * @return object A class instance
   * @throws \LogicException
   */
  public static function create(string $class, string $message = '', int $code = 0, \Throwable $previous = null, ...$extra) {
    if (substr($class, 0, 1) != '\\') {
      $namespace = static::class;
      $clsPos = strrpos($namespace, '\\');
      if ($clsPos !== false) {
        $namespace = substr($namespace, 0, $clsPos);
      }
      $class = $namespace . '\\' . $class;
    }
    if (class_exists($class, true)) {
      return new $class($message, $code, $previous, ...$extra);
    } else {
      throw new \LogicException("Could not create exception of type $class, the class does not exists");
    }
  }

  /**
   * Returns an instance of this class.
   *
   * @param string $message  The exception message
   * @param int    $code     The exception code
   * @param object $previous An optional previous exception object.
   * @param mixed  ...$extra Any number of extra parameters of any type.
   *
   * @return object A class instance
   * @throws \LogicException
   */
  public static function createSelf(string $message = '', int $code = 0, \Throwable $previous = null, ...$extra) {
    return self::create('\\' . static::class, $message, $code, $previous, ...$extra);
  }
}
