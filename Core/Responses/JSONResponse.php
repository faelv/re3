<?php

/**
 * JSONResponse class
 *
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Responses;

use Core\Responses\CacheableResponse;
use Core\Exceptions\ResponseException;

/**
 * Response class for JSON content.
 *
 * @package Core
 */
class JSONResponse extends CacheableResponse {

  /**
   * Stores the response content array.
   * @var array
   */
  protected $content = [];

  /**
   * Constructor. Throws an exception if the "content" parameter is a JSON string and it fails to be decoded.
   *
   * @param array|string $content An array or JSON string
   * @throws \Core\Exceptions\ResponseException
   */
  public function __construct($content = null) {
    if (!is_null($content)) {
      if (is_array($content)) {
        $this->setContentArray($content);
      } elseif (is_string($content)) {
        $this->setContentString($content);
      } else {
        throw ResponseException::createSelf('The "content" parameter should be an array or a string');
      }
    }
  }

  /**
   * Sets the content array that this response will output.
   *
   * @param array $content An array.
   *
   * @return void
   */
  public function setContentArray(array $content) {
    $this->content = $content;
  }

  /**
   * Converts the JSON string to an array and sets the content array that this response will output. Throws an exception
   * if the JSON string fails to be decoded.
   *
   * @param string $content A JSON string
   *
   * @return void
   * @throws \Core\Exceptions\ResponseException
   */
  public function setContentString(string $content) {
    $arr = json_decode($content, true);
    if (is_array($arr)) {
      $this->content = $arr;
    } else {
      throw ResponseException::createSelf('The "content" parameter is an invalid JSON string');
    }
  }

  /**
   * Outputs HTTP response headers.
   *
   * @return void
   */
  protected function outputHeaders() {
    $this->setHeader('Content-Type', $this->httpUtils->MIMETypeFromExtension('json'));
    parent::outputHeaders();
  }

  /**
   * Outputs the data array as JSON.
   *
   * @return void
   */
  protected function outputContent() {
    echo json_encode($this->content);
  }

}
