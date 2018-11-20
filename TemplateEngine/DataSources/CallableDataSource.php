<?php

/**
 * CallableDataSource class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace TemplateEngine\DataSources;

use TemplateEngine\DataSources\DataSource;

/**
 * Data source class for callables.
 *
 * @package TemplateEngine
 */
class CallableDataSource extends DataSource {

  /**
   * Stores the callable.
   * @var callable
   */
  protected $callable = null;

  /**
   * Constructor.
   *
   * @param callable $callable A callable. It must return a string.
   */
  public function __construct(callable $callable) {
    $this->callable = $callable;
  }

  /**
   * Returns a string representation of the data of this source.
   *
   * @return string
   */
  public function __toString() : string {
    if (!is_null($this->callable)) {
      return (string)($this->callable)();
    }
    return '';
  }

}
