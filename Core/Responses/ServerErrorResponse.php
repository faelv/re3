<?php

/**
 * ServerErrorResponse class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Responses;

use Core\Responses\UnsucessfulResponse;

/**
 * Response class for server errors.
 *
 * @package Core
 */
class ServerErrorResponse extends UnsucessfulResponse {

  /**
   * Outputs the HTTP status header.
   *
   * @return void
   */
  protected function outputStatus() {
    $this->statusCode = $this->httpUtils::STATUS_INTERNAL_SERVER_ERROR;
    parent::outputStatus();
  }

}
