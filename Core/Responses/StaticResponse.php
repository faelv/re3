<?php

/**
 * StaticResponse class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Responses;

use Core\Responses\CacheableResponse;

/**
 * Response class that outputs file contents.
 *
 * @package Core
 */
class StaticResponse extends CacheableResponse {

  /**
   * Stores the file object
   * @var \Core\FileSystem\File
   */
  protected $file = null;

  /**
   * Sets the file object.
   *
   * @param \Core\FileSystem\File $file A file object
   *
   * @return void
   */
  public function setFile(\Core\FileSystem\File $file) {
    $this->file = $file;
  }

  /**
   * Returns the current file object
   *
   * @return \Core\FileSystem\File
   */
  public function getFile() : \Core\FileSystem\File {
    return $this->file;
  }

  /**
   * Outputs HTTP response headers.
   *
   * @return void
   */
  protected function outputHeaders() {
    if (!is_null($this->file)) {
      $this->setHeader('Content-Type', $this->httpUtils->MIMETypeFromExtension($this->file->extension()));
      if (false !== $length = $this->file->size()) {
        $this->setHeader('Content-Length', (string)$length);
      }
      $this->setModifiedDate($this->file->modifiedDate());
    }
    parent::outputHeaders();
  }

  /**
   * Outputs the content body.
   *
   * @return void
   */
  protected function outputContent() {
    if (!is_null($this->file)) {
      if (!$this->file->isOpen()) {
        $this->file->open($this->file::MODE_READ);
      } else {
        $this->file->set(0);
      }
      $this->file->output();
      if ($this->file->isOpen()) {
        $this->file->close();
      }
    }
  }

}
