<?php

/**
 * HTTPRequest class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Requests;

use Core\Requests\Request;

/**
 * A request received by the server.
 *
 * @package Core
 */
class HTTPRequest extends Request {

  /**
   * An URL query (GET) parameter.
   */
  const DATA_QUERY = 0;

  /**
   * A POST parameter.
   */
  const DATA_POST = 1;

  /**
   * A cookie.
   */
  const DATA_COOKIE = 2;

  /**
   * An URI path parameter.
   */
  const DATA_URI_PARAM = 3;

  /**
   * An uploaded file info.
   */
  const DATA_UPLOADED_FILE = 4;

  /**
   * The raw POST input
   */
  const DATA_POST_RAW = 5;

  /**
   * Stores URI parameters
   * @var array
   */
  protected $URIData = [];

  /**
   * Request URI
   * @var string
   */
  protected $URI = '';

  /**
   * Request query string.
   * @var string
   */
  protected $queryString = '';

  /**
   * Request method
   * @var string
   */
  protected $method = '';

  /**
   * Constructor
   */
  public function __construct() {
    $reqURI = $this->getHeader('REQUEST_URI', '');
    if (false !== $qsPos = strpos($reqURI, '?')) {
      $this->URI = substr($reqURI, 0, $qsPos);
      $this->queryString = substr($reqURI, $qsPos);
    } else {
      $this->URI = $reqURI;
      $this->queryString = '';
    }
    $this->method = strtoupper($this->getHeader('REQUEST_METHOD', ''));
  }

  /**
   * Returns the request URI (excluding the query string).
   *
   * @param bool $includeQueryString True to return the URI with the query string, if any.
   *
   * @return string The request URI.
   */
  public function getURI(bool $includeQueryString = false) : string {
    if ($includeQueryString) {
      return $this->URI . $this->queryString;
    } else {
      return $this->URI;
    }
  }

  /**
   * Returns the request method.
   *
   * @return string The request method.
   */
  public function getMethod() : string {
    return $this->method;
  }

  /**
   * Returns data sent with the request.
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
      case self::DATA_COOKIE:
        $value = isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
        break;
      case self::DATA_POST:
        $value = isset($_POST[$name]) ? $_POST[$name] : $default;
        break;
      case self::DATA_POST_RAW:
        $value = file_get_contents('php://input');
        break;
      case self::DATA_QUERY:
        $value = isset($_GET[$name]) ? $_GET[$name] : $default;
        break;
      case self::DATA_URI_PARAM:
        $value = isset($this->URIData[$name]) ? $this->URIData[$name] : $default;
        break;
      case self::DATA_UPLOADED_FILE:
        $value = isset($_FILES[$name]) ? $_FILES[$name] : $default;
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
   * Returns all data of a type sent with the request
   *
   * @param int $type Source of the data, one of the DATA_* constants.
   *
   * @return array An array containing the data
   * @throws \LogicException
   */
  public function getAllData(int $type) : array {
    $value = [];

    switch ($type) {
      case self::DATA_COOKIE:
        $value = $_COOKIE;
        break;
      case self::DATA_POST:
        $value = $_POST;
        break;
      case self::DATA_POST_RAW:
        $value = [file_get_contents('php://input')];
        break;
      case self::DATA_QUERY:
        $value = $_GET;
        break;
      case self::DATA_URI_PARAM:
        $value = $this->URIData;
        break;
      case self::DATA_UPLOADED_FILE:
        $value = $_FILES;
        break;
      default:
        throw new \LogicException('Invalid parameter: type');
    }

    return $value;
  }

  /**
   * Returns the value of a GET parameter.
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
  public function getQueryData(string $name, $default = null, bool $defaultOnEmpty = false, callable $sanitizeFunc = null) {
    return $this->getData(self::DATA_QUERY, $name, $default, $defaultOnEmpty, $sanitizeFunc);
  }

  /**
   * Returns the value of a POST parameter.
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
  public function getPostData(string $name, $default = null, bool $defaultOnEmpty = false, callable $sanitizeFunc = null) {
    return $this->getData(self::DATA_POST, $name, $default, $defaultOnEmpty, $sanitizeFunc);
  }

  /**
   * Returns the raw value of a POST input.
   *
   * @param mixed    $default        Default value if parameter is not set
   * @param bool     $defaultOnEmpty True if the default value should be used instead of the current value if it's
   *                                 considered empty
   * @param callable $sanitizeFunc   A function to sanitize the returned value. Receives one paramater (the value) and
   *                                 returns the new value.
   *
   * @return mixed The parameter value or the default value if it's not set
   */
  public function getPostRawData($default = null, bool $defaultOnEmpty = false, callable $sanitizeFunc = null) {
    return $this->getData(self::DATA_POST_RAW, '', $default, $defaultOnEmpty, $sanitizeFunc);
  }

  /**
   * Returns the value of a URI parameter.
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
   * Returns the value of a cookie.
   *
   * @param string   $name           Cookie's name
   * @param mixed    $default        Default value if cookie is not set
   * @param bool     $defaultOnEmpty True if the default value should be used instead of the current value if it's
   *                                 considered empty
   * @param callable $sanitizeFunc   A function to sanitize the returned value. Receives one paramater (the value) and
   *                                 returns the new value.
   *
   * @return mixed The cookie value or the default value if it's not set
   */
  public function getCookieData(string $name, $default = null, bool $defaultOnEmpty = false, callable $sanitizeFunc = null) {
    return $this->getData(self::DATA_COOKIE, $name, $default, $defaultOnEmpty, $sanitizeFunc);
  }

  /**
   * Returns information on a uploaded file.
   *
   * @param string   $name           File input name
   * @param mixed    $default        Default value if file is not set
   * @param bool     $defaultOnEmpty True if the default value should be used instead of the current value if it's
   *                                 considered empty
   * @param callable $sanitizeFunc   A function to sanitize the returned value. Receives one paramater (the value) and
   *                                 returns the new value.
   *
   * @return mixed The file information array or the default value if it's not set
   */
  public function getUploadedFileData(string $name, $default = null, bool $defaultOnEmpty = false, callable $sanitizeFunc = null) {
    return $this->getData(self::DATA_UPLOADED_FILE, $name, $default, $defaultOnEmpty, $sanitizeFunc);
  }

  /**
   * Returns a header value.
   *
   * @param string $name    Header's name
   * @param mixed  $default Default value if header isn't set
   *
   * @return mixed Header value or the default value if the header is not set
   */
  public function getHeader(string $name, $default = null) {
    return isset($_SERVER[$name]) ? $_SERVER[$name] : $default;
  }

  /**
   * Returns an array with all headers.
   *
   * @return array An array in a key => value formats
   */
  public function getAllHeaders() : array {
    return $_SERVER;
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
