<?php

/**
 * DownloadResponse class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Responses;

use Core\Responses\StaticResponse;

/**
 * Response class for downloadable files. Intended to be used for downloads that trigger the browser download dialog.
 *
 * @package Core
 */
class DownloadResponse extends StaticResponse {

  /**
   * Stores the download's suggested file name.
   * @var string
   */
  protected $name = '';

  /**
   * Stores the maximum download speed value in KB/s.
   * @var float
   */
  protected $rateLimit = 0;

  /**
   * Sets the download's suggested file name.
   *
   * @param string $value Filename
   *
   * @return void
   */
  public function setName(string $value) {
    $this->name = $value;
  }

  /**
   * Returns the current download's suggsted file name.
   *
   * @return string
   */
  public function getName() : string {
    return $this->name;
  }

  /**
   * Sets the maximum download speed in KB/s.
   *
   * @param float $value Speed in KB/s.
   *
   * @return void
   */
  public function setRateLimit(float $value) {
    $this->rateLimit = $value;
  }

  /**
   * Returns the current maximum download speed in KB/s.
   *
   * @return float
   */
  public function getRateLimit() : float {
    return $this->rateLimit;
  }

  /**
   * Disables the download speed limit. Same as setting the limit to zero.
   *
   * @return void
   */
  public function disableRateLimit() {
    $this->setRateLimit(0);
  }

  /**
   * Outputs HTTP response headers.
   *
   * @return void
   */
  protected function outputHeaders() {
    if (!is_null($this->file)) {
      if (empty($this->name)) {
        $this->name = $this->file->name(true);
      }
      $attachmentName = empty($this->name) ? '' : '; filename="' . $this->name .'"';
      $this->setHeader('Content-Disposition', 'attachment' . $attachmentName);
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
      if (!$this->rateLimit) {
        parent::outputContent();
      } else {
        flush();
        if (!$this->file->isOpen()) {
          $this->file->open($this->file::MODE_READ);
        } else {
          $this->file->set(0);
        }
        while (!$this->file->eof()) {
          echo $this->file->read((int)round($this->rateLimit * 1024, 0, PHP_ROUND_HALF_EVEN));
          flush();
          sleep(1);
        }
        if ($this->file->isOpen()) {
          $this->file->close();
        }
      }
    }
  }

}
