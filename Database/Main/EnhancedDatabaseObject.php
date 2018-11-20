<?php

/**
 * EnhancedDatabaseObject class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Database\Main;

use Database\Main\DatabaseObject;
use Database\Main\DatabaseStatement;

/**
 * An enhanced DatabaseObject class with property type conversion.
 *
 * @package Database
 */
abstract class EnhancedDatabaseObject extends DatabaseObject {

  /**
   * Do not perform any type cast.
   */
  const CAST_NONE = 0;

  /**
   * Cast to an string if necessary.
   */
  const CAST_STRING = 1;

  /**
   * Cast to a boolean.
   */
  const CAST_BOOL = 2;

  /**
   * Cast to a DateTime object if possible.
   */
  const CAST_DATETIME = 3;

  /**
   * Cast to an integer. The fractional part (if any) will be discarded.
   */
  const CAST_INTEGER = 4;

  /**
   * Cast to a float.
   */
  const CAST_FLOAT = 5;

  /**
   * If a property has a value of null and this flag is True, null will be kept as the value instead of being cast
   * to the desired type.
   * @var boolean
   */
  static protected $preserveNullOnCast = false;

  /**
   * Returns the property's cast method with some additional information.
   *
   * @param string   $name Property's name.
   * @param float    $max  Property's maximum value for numbers or length for strings (by reference).
   * @param float    $min  Property's minimum value for number (by reference).
   * @param callable $func A callable for further processing. Receives 3 parameters: the property's value (by reference),
   *                       name, and the current cast constant.
   *
   * @return integer One of the CAST_* constants. Defaults to CAST_NONE.
   */
  protected static function getPropertyCasting(
    string $name, float &$max = null, float &$min = null, callable $func = null
  ) : int {
    return self::CAST_NONE;
  }

  /**
   * Indicates if a property is read only.
   *
   * @param string $name Property's name.
   *
   * @return bool If true assignements will only be accpedted when the object's state is UNINITIALIZED. Defaults to
   * false.
   */
  protected static function isPropertyReadOnly(string $name) : bool {
    return false;
  }

  /**
   * Performs a type casting on the value of a property.
   *
   * @param string $name  Property's name.
   * @param mixed  $value Property's value (by reference).
   *
   * @return void
   */
  protected static function castPropertyValue(string $name, &$value) {
    $max = null;
    $min = null;
    $func = null;
    $casting = static::getPropertyCasting($name, $max, $min, $func);

    switch ($casting) {
      case self::CAST_STRING:
        if (is_null($value) && static::$preserveNullOnCast) {
          break;
        }

        if (!is_string($value)) {
          $value = (string)$value;
        }

        if (!is_null($max) && mb_strlen($value) > $max) {
          $value = mb_substr($value, 0, (int)$max);
        }
        break;

      case self::CAST_BOOL:
        if (is_null($value) && static::$preserveNullOnCast) {
          break;
        }
        $value = (bool)$value;
        break;

      case self::CAST_DATETIME:
        if ($value instanceof \DateTime) {
          break;
        }

        if (!is_int($value) && !is_string($value)) {
          $value = false;
        } else {
          $value = (string)$value;
          if (!preg_match('/[^0-9]/', $value)) {
            $value = \DateTime::createFromFormat('U', $value);
          } else {
            $value = \DateTime::createFromFormat(DatabaseStatement::getDateTimeFormat(), $value);
          }
        }
        if ($value === false) {
          $value = null;
        }
        break;

      case self::CAST_INTEGER:
        if (is_null($value) && static::$preserveNullOnCast) {
          break;
        }

        $value = (int)$value;
        if (!is_null($min)) {
          $min = (int)$min;
          $value = $value < $min ? $min : $value;
        } elseif (!is_null($max)) {
          $max = (int)$max;
          $value = $value > $max ? $max : $value;
        }
        break;

      case self::CAST_FLOAT:
        if (is_null($value) && static::$preserveNullOnCast) {
          break;
        }

        $value = (float)$value;
        if (!is_null($min)) {
          $value = $value < $min ? $min : $value;
        } elseif (!is_null($max)) {
          $value = $value > $max ? $max : $value;
        }
        break;
    }

    if (is_callable($func)) {
      $func($value, $name, $casting);
    }
  }

  /**
   * Validates a property assignment. A callback that can accept or reject the property assignment and modify the
   * passed value.
   *
   * @param string $name  Name of the property.
   * @param mixed  $value Value of the property (by reference).
   *
   * @return bool True to accept the assignment, False otherwise.
   */
  protected function onPropertySet(string $name, &$value) : bool {
    if ($this->getState() != self::STATE_UNINITIALIZED && static::isPropertyReadOnly($name)) {
      return false;
    }
    static::castPropertyValue($name, $value);
    return true;
  }

}
