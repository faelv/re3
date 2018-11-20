<?php

/**
 * CLIRequest class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Requests;

use Core\Requests\Request;

/**
 * Class for command line (CLI) requests.
 *
 * @package Core
 */
class CLIRequest extends Request {

  /**
   * An URI path parameter.
   */
  const DATA_URI_PARAM = 3;

  /**
   * A command line argument
   */
  const DATA_ARGUMENT = 5;

  /**
   * Stores URI parameters
   * @var array
   */
  protected $URIData = [];

  /**
   * Stores command line arguments.
   * @var array
   */
  protected $arguments = [];

  /**
   * Request URI
   * @var string
   */
  protected $URI = '';

  /**
   * Constructor.
   */
  public function __construct() {
    $args = array_get_if_set($_SERVER, 'argv', []);
    if (count($args) > 1) {
      $this->URI = $args[1];
      parse_str(implode('&', array_slice($args, 2)), $this->arguments);
    }
  }

  /**
   * Returns the request URI.
   *
   * For a CLIRequest the request URI is the <b>first argument</b> passed via command line. It's behavior and format
   * is the same as an HTTP URI.
   * <code>php /path/to/resource</code>
   *
   * @return string The request's URI. If no arguments are provided, returns an empty string.
   */
  public function getURI() : string {
    return $this->URI;
  }

  /**
   * Returns request's data.
   *
   * @param int      $type           Source of the data, one of the DATA_* constants.
   * @param string   $name           Parameter name
   * @param mixed    $default        Default value if parameter is not set
   * @param bool     $defaultOnEmpty True if the default value should be used instead of the current value if it's
   *                                 considered empty
   * @param callable $sanitizeFunc   A function to sanitize the returned value. Receives one paramater (the value) and
   *                                 returns the new value.
   *
   * @return mixed The parameter value or the default value if it's not set
   * @throws \LogicException
   */
  public function getData(int $type, string $name, $default = null, bool $defaultOnEmpty = false, callable $sanitizeFunc = null) {
    switch ($type) {
      case self::DATA_URI_PARAM:
        $value = isset($this->URIData[$name]) ? $this->URIData[$name] : $default;
        break;
      case self::DATA_ARGUMENT:
        $value = isset($this->arguments[$name]) ? $this->arguments[$name] : $default;
        break;
      default:
        throw new \LogicException('Invalid parameter: type');
    }

    if ($value !== $default) {
      $empty = false;
      if (!is_array($value)) {
        $value = trim($value);
        $empty = ($value == '');
      } else {
        $empty = (count($value) < 1);
      }
      if ($empty && $defaultOnEmpty) {
        $value = $default;
      } elseif (is_callable($sanitizeFunc)) {
        $value = $sanitizeFunc($value);
      }
    }

    return $value;
  }

  /**
   * Returns the value of a URI parameter
   *
   * @param string   $name           Parameter name
   * @param mixed    $default        Default value if parameter is not set
   * @param bool     $defaultOnEmpty True if the default value should be used instead of the current value if it's
   *                                 considered empty
   * @param callable $sanitizeFunc   A function to sanitize the returned value. Receives one paramater (the value) and
   *                                 returns the new value.
   *
   * @return mixed The parameter value or the default value if it's not set
   */
  public function getURIData(string $name, $default = null, bool $defaultOnEmpty = false, callable $sanitizeFunc = null) {
    return $this->getData(self::DATA_URI_PARAM, $name, $default, $defaultOnEmpty, $sanitizeFunc);
  }

  /**
   * Returns the value of a command line argument. A command line argument should be given in a name=value format.
   * Arrays are supported, just put [] in front of the command name and repeat this name with different values:
   * <code>php path/to/resource arg1=value1 arg2[]=value2 arg2[]=value3</code>
   *
   * @param string   $name           Parameter name
   * @param mixed    $default        Default value if parameter is not set
   * @param bool     $defaultOnEmpty True if the default value should be used instead of the current value if it's
   *                                 considered empty
   * @param callable $sanitizeFunc   A function to sanitize the returned value. Receives one paramater (the value) and
   *                                 returns the new value.
   *
   * @return mixed The parameter value or the default value if it's not set
   */
  public function getArgumentData(string $name, $default = null, bool $defaultOnEmpty = false, callable $sanitizeFunc = null) {
    return $this->getData(self::DATA_ARGUMENT, $name, $default, $defaultOnEmpty, $sanitizeFunc);
  }

  /**
   * Adds URI data parameters to the request
   *
   * @param string $name  Name of URI data parameter
   * @param string $value Value of URI data parameter
   *
   * @return void
   */
  public function setURIData(string $name, string $value) {
    $this->URIData[$name] = $value;
  }

}
