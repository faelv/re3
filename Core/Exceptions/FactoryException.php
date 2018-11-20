<?php

/**
 * FactoryException class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Exceptions;

use Core\Exceptions\ApplicationException;

/**
 * Exception for problems related to the class factories
 *
 * @package Core
 */
class FactoryException extends ApplicationException {

  /**
   * Stores the name of the class whose creation failed.
   * @var string
   */
  protected $class = '';

  /**
   * Constructor.
   *
   * @param string     $message  Exception message.
   * @param int        $code     Exception code.
   * @param \Throwable $previous A previous exception if any.
   * @param mixed      ...$extra Any number of parameters of any type. For this class the first extra parameter is name
   *                             of the class whose creation failed.
   */
  public function __construct(string $message = '', int $code = 0, \Throwable $previous = null, ...$extra) {
    parent::__construct($message, $code, $previous, ...$extra);
    if (count($extra) > 0) {
      $this->class = $extra[0];
    }
  }

  /**
   * Returns the name of the class whose creation failed.
   *
   * @return string
   */
  final public function getClass() {
    return $this->class;
  }
}
