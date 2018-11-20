<?php

/**
 * Polyfills class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// The above MissingNamespace directive is for the Polyfills class only!

/**
 * Class responsable for defining global functions.
 *
 * @package Core
 */
class Polyfills {

  /**
   * An instance of this class.
   * @var \Polyfills
   */
  private static $instance = null;

  /**
   * Constructor.
   */
  private function __construct() {
    //you shall not... be called!
  }

  /**
   * Defines global functions. Only put functions here that really need to be globally accessible.
   *
   * @return void
   */
  private function functions() {
    //-----------------------------------------------------------------------------

    /**
     * Determines if the server HTTPS flag is set.
     *
     * @return bool True if using HTTPS, False otherwise.
     */
    function is_server_https() {
      if (isset($_SERVER['HTTPS'])) {
        return !in_array(trim(strtolower($_SERVER['HTTPS'])), ['off', '0', 'false', '']);
      }
      return false;
    }

    /**
     * Returns the current date and time with microseconds.
     *
     * @return string The date an time in the "Y/m/d H:i:s.u" format.
     */
    function microsec_now() {
      list($secs, $usecs) = explode('.', (string)microtime(true) . '.0');
      return date('Y/m/d H:i:s', (int)$secs) . ".$usecs";
    }

    /**
     * Formats a value expressed in bytes to a more "human readable" string. Adapted from a php.net example.
     *
     * @param int $bytes    The value in bytes.
     * @param int $decimals The number of decimal digits.
     *
     * @return string The formatted value.
     * @link http://php.net/manual/en/function.filesize.php
     */
    function nice_bytes(int $bytes, int $decimals = 2) {
      $sz = ['B', 'KiB', 'MiB', 'GiB'];
      $factor = min(3, floor((strlen($bytes) - 1) / 3));

      return sprintf("%.{$decimals}f ", $bytes / pow(1024, $factor)) . $sz[$factor];
    }

    /**
     * Sets the value of a variable reference if the passed value is not null.
     *
     * @param mixed $var   Reference to a variable.
     * @param mixed $value The value.
     *
     * @return void
     */
    function not_null_set(&$var, $value) {
      if (!is_null($value)) {
        $var = $value;
      }
    }

    /**
     * Sets the value of a variable reference if the passed value is not empty.
     *
     * @param mixed $var   Reference to a variable.
     * @param mixed $value The value.
     *
     * @return void
     */
    function not_empty_set(&$var, $value) {
      if (!empty($value)) {
        $var = $value;
      }
    }

    /**
     * Returns a value from an array element or a default value if the key does not exists.
     *
     * @param array $array       An array.
     * @param mixed $key         Element key.
     * @param mixed $default     Default value if key is not found.
     * @param bool  $nullIsValid True if an element with a value of Null will be considered set.
     *
     * @return mixed Element value or default value.
     */
    function array_get_if_set($array, $key, $default = null, $nullIsValid = false) {
      if ($nullIsValid) {
        return array_key_exists($key, $array) ? $array[$key] : $default;
      } else {
        return isset($array[$key]) ? $array[$key] : $default;
      }
    }

    /**
     * Checks if a set of keys exists in a array.
     *
     * @param array $keys  An array with keys.
     * @param array $array An array.
     *
     * @return bool True if all keys are set, False oherwise.
     */
    function array_keys_exists($keys, $array) {
      foreach ($keys as $key) {
        if (!array_key_exists($key, $array)) {
          return false;
        }
      }
      return true;
    }

    //-----------------------------------------------------------------------------
  }

  /**
   * Creates a singleton instance of this class and defines the global functions.
   *
   * @return void
   */
  public static function define() {
    if (is_null(self::$instance)) {
      self::$instance = new self();
      self::$instance->functions();
    }
  }

}
