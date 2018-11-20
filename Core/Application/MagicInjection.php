<?php

/**
 * MagicInjection trait
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Application;

/**
 * Trait used for dependency injection of non public properties.
 *
 * @package Core
 */
trait MagicInjection {

  /**
   * Magic method. Sets inaccessible properties.
   *
   * @param string $name  Property name.
   * @param mixed  $value Property value.
   *
   * @return void
   */
  public function __set(string $name, $value) {
    if (property_exists($this, $name)) {
      $this->$name = $value;
    }
  }

}
