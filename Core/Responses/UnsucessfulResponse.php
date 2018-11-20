<?php

/**
 * UnsucessfulResponse class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Responses;

use Core\Responses\HTTPResponse;

/**
 * Response class for unsucessful requests.
 *
 * @package Core
 */
class UnsucessfulResponse extends HTTPResponse {

  /**
   * Stores the response message
   * @var string
   */
  protected $text = '';

  /**
   * Sets the response message.
   *
   * @param string $value Message.
   *
   * @return void
   */
  public function setText(string $value) {
    $this->text = $value;
  }

  /**
   * Returns the current response message
   * @return string
   */
  public function getText() : string {
    return $this->text;
  }

  /**
   * Outputs the HTTP status header.
   *
   * @return void
   */
  protected function outputStatus() {
    $this->statusString = $this->httpUtils->statusStringFromCode($this->statusCode);
    parent::outputStatus();
  }

  /**
   * Outputs the content body.
   *
   * @return void
   */
  protected function outputContent() {
    parent::outputContent();
    if (empty($this->text)) {
      echo $this->statusString;
    } else {
      echo $this->text;
    }
  }

}
