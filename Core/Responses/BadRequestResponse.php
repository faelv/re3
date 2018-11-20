<?php

/**
 * BadRequestResponse class.
 *
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Responses;

use Core\Responses\UnsucessfulResponse;

/**
 * Response class for bad requests
 *
 * @package Core
 */
class BadRequestResponse extends UnsucessfulResponse {

  /**
   * Outputs the HTTP status header.
   *
   * @return void
   */
  protected function outputStatus() {
    $this->statusCode = $this->httpUtils::STATUS_BAD_REQUEST;
    parent::outputStatus();
  }

}
