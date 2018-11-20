<?php

/**
 * NotModifiedResponse class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Responses;

use Core\Responses\CacheableResponse;

/**
 * Response class for not modified resources.
 *
 * @package Core
 */
class NotModifiedResponse extends CacheableResponse {

  /**
   * Outputs the HTTP status header.
   *
   * @return void
   */
  protected function outputStatus() {
    $this->statusCode = $this->httpUtils::STATUS_NOT_MODIFIED;
    $this->statusString = $this->httpUtils->statusStringFromCode($this->statusCode);
    parent::outputStatus();
  }

}
