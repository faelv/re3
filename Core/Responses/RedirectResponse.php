<?php

/**
 * ResirectResponse class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Responses;

use Core\Responses\CacheableResponse;

/**
 * Response class for HTTP redirection.
 *
 * @package Core
 */
class RedirectResponse extends CacheableResponse {

  /**
   * Stores the permanent flag of the redirection.
   * @var boolean
   */
  protected $permanent = false;

  /**
   * Stores the redirection location
   * @var string
   */
  protected $location = '';

  /**
   * Constructor.
   *
   * @param string $location  Redirection location.
   * @param bool   $permanent True to use the permanent redirection HTTP status code or False to use the found HTTP
   *                          status code. Defaults to false.
   *
   * @return void
   */
  public function __construct(string $location = null, bool $permanent = false) {
    if (!is_null($location)) {
      $this->setLocation($location);
    }
    $this->setPermanent($permanent);
  }

  /**
   * Sets the permanent redirection flag.
   *
   * @param bool $value True for a permanent redirect.
   *
   * @return void
   */
  public function setPermanent(bool $value) {
    $this->permanent = $value;
  }

  /**
   * Sets the redirection location/target.
   *
   * @param string $value Location.
   *
   * @return void
   */
  public function setLocation(string $value) {
    $this->location = $value;
  }

  /**
   * Returns the current permanent flag value.
   *
   * @return boolean
   */
  public function getPermanent() : bool {
    return $this->permanent;
  }

  /**
   * Returns the current redirection location/target.
   *
   * @return string
   */
  public function getLocation() : string {
    return $this->location;
  }

  /**
   * Outputs the HTTP status header.
   *
   * @return void
   */
  protected function outputStatus() {
    $this->statusCode = $this->permanent ? $this->httpUtils::STATUS_MOVED_PERMANENTLY : $this->httpUtils::STATUS_FOUND;
    $this->statusString = $this->httpUtils->statusStringFromCode($this->statusCode);
    parent::outputStatus();
  }

  /**
   * Outputs HTTP response headers.
   *
   * @return void
   */
  protected function outputHeaders() {
    $this->setHeader('Location', $this->location);
    $this->setCacheable(false);
    parent::outputHeaders();
  }

}
