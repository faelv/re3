<?php

/**
 * HTTPResponse class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Responses;

use Core\Responses\Response;
use Core\Application\MagicInjection;

/**
 * Base class for responses that work with the HTTP protocol.
 *
 * @package Core
 */
class HTTPResponse extends Response {

  use MagicInjection;

  /**
   * Stores a reference to the HTTPUtils class. Injection target.
   * @var \Core\Utils\HTTPUtils
   */
  protected $httpUtils;

  /**
   * Stores header data as pairs of name => value
   * @var array
   */
  protected $headers = [];

  /**
   * Stores cookie data as pairs of name => value
   * @var arrsy
   */
  protected $cookies = [];

  /**
   * The HTTP response status code.
   * @var int
   */
  protected $statusCode = 200;

  /**
   * The HTTP response status string.
   * @var string
   */
  protected $statusString = 'OK';

  /**
   * HTTP protocol version
   * @var string
   */
  protected $httpVersion = '1.1';

  /**
   * Outputs the HTTP status header.
   *
   * @return void
   */
  protected function outputStatus() {
    header('HTTP/' . $this->httpVersion . ' ' . $this->statusCode . ' ' . $this->statusString, true, $this->statusCode);
  }

  /**
   * Outputs HTTP response headers.
   *
   * @return void
   */
  protected function outputHeaders() {
    foreach ($this->headers as $name => $value) {
      header("$name: $value", true);
    }
  }

  /**
   * Outputs HTTP response cookie headers.
   *
   * @return void
   */
  protected function outputCookies() {
    $https = is_server_https();
    foreach ($this->cookies as $name => $items) {
      if ($items['secure'] && !$https) {
        continue;
      }
      setcookie(
        $name, $items['value'],
        $items['expire'], $items['path'], $items['domain'], $items['secure'], $items['httponly']
      );
    }
  }

  /**
   * Outputs the content body.
   *
   * @return void
   */
  protected function outputContent() {
  }

  /**
   * Sets the HTTP status code.
   *
   * @param int $value Status code
   *
   * @return void
   */
  public function setStatusCode(int $value) {
    $this->statusCode = $value;
  }

  /**
   * Returns the current HTTP status code.
   *
   * @return int
   */
  public function getStatusCode() : int {
    return $this->statusCode;
  }

  /**
   * Sets the HTTP status string.
   *
   * @param string $value Status string
   *
   * @return void
   */
  public function setStatusString(string $value) {
    $this->statusString = $value;
  }

  /**
   * Returns the current HTTP status string.
   *
   * @return string
   */
  public function getStatusString() : string {
    return $this->statusString;
  }

  /**
   * Adds a new HTTP header.
   *
   * @param string $name  Header name
   * @param string $value Header value
   *
   * @return void
   */
  public function setHeader(string $name, string $value) {
    $this->headers[$name] = $value;
  }

  /**
   * Adds a new HTTP cookie header.
   *
   * @param string $name     The name of the cookie.
   * @param string $value    The value of the cookie.
   * @param int    $expire   The cookie absolute expiration time as a timestamp.
   * @param string $path     The path on the server in which the cookie will be available on.
   * @param string $domain   The (sub)domain that the cookie is available to.
   * @param bool   $secure   Indicates that the cookie should only be transmitted over a secure HTTPS connection.
   * @param bool   $httponly When TRUE the cookie will be made accessible only through the HTTP protocol.
   *
   * @return void
   * @see http://php.net/manual/en/function.setcookie.php PHP's setCookie function.
   */
  public function setCookie(
    string $name,
    string$value = '',
    int $expire = 0,
    string $path = '/',
    string $domain = '',
    bool $secure = false,
    bool $httponly = false
  ) {
    $this->cookies[$name] = [
        'value'    => $value,
        'expire'   => $expire,
        'path'     => $path,
        'domain'   => $domain,
        'secure'   => $secure,
        'httponly' => $httponly,
    ];
  }

  /**
   * Adds a new HTTP cookie header, but sets the expiration time in the past to force the cookie expiration.
   *
   * @param string $name     The name of the cookie.
   * @param string $path     The path on the server in which the cookie will be available on.
   * @param string $domain   The (sub)domain that the cookie is available to.
   * @param bool   $secure   Indicates that the cookie should only be transmitted over a secure HTTPS connection.
   * @param bool   $httponly When TRUE the cookie will be made accessible only through the HTTP protocol.
   *
   * @return void
   * @see http://php.net/manual/en/function.setcookie.php PHP's setCookie function.
   */
  public function unsetCookie(
    string $name, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false
  ) {
    $this->cookies[$name] = [
        'value'    => '',
        'expire'   => time() - 3600,
        'path'     => $path,
        'domain'   => $domain,
        'secure'   => $secure,
        'httponly' => $httponly,
    ];
  }

  /**
   * Outputs this response's data.
   *
   * @return void
   */
  final public function output() {
    $this->outputStatus();
    $this->outputHeaders();
    $this->outputCookies();
    $this->outputContent();
  }

}
