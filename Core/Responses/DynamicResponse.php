<?php

/**
 * DynamicResponse class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Responses;

use Core\Responses\CacheableResponse;

/**
 * Response class for dynamically generated content.
 *
 * @package Core
 */
class DynamicResponse extends CacheableResponse {

  /**
   * Stores the response output data.
   * @var string|callable
   */
  protected $content;

  /**
   * Sets the content that this response will output.
   *
   * @param string|callable $content The content itself or a callable that returns it.
   *
   * @return void
   */
  public function setContent($content) {
    $this->content = $content;
  }

  /**
   * Returns the current content
   *
   * @return string|callable
   */
  public function getContent() {
    return $this->content;
  }

  /**
   * Outputs the content body.
   *
   * @return void
   */
  protected function outputContent() {
    if (is_callable($this->content)) {
      $callableContent = $this->content;
      echo $callableContent();
    } else {
      echo $this->content;
    }
  }

}
