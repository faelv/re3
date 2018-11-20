<?php

/**
 * CacheableResponse class.
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Responses;

use Core\Responses\HTTPResponse;

/**
 * Base class for HTTP derived responses with cache settings.
 *
 * @package Core
 */
class CacheableResponse extends HTTPResponse {

  /**
   * Seconds time unit.
   */
  const AGE_SECONDS = 0;

  /**
   * Minutes time unit.
   */
  const AGE_MINUTES = 1;

  /**
   * Hours time unit.
   */
  const AGE_HOURS = 2;

  /**
   * Days time unit.
   */
  const AGE_DAYS = 3;

  /**
   * Flag to enable or disable caching.
   * @var boolean
   */
  protected $cacheable = true;

  /**
   * Flag to enable cache revalidation.
   * @var boolean
   */
  protected $revalidate = false;

  /**
   * Flag to set cache privacy as private or not.
   * @var boolean
   */
  protected $private = false;

  /**
   * Stores the cache max age in seconds.
   * @var int
   */
  protected $age = 14400;

  /**
   * Stores the response ETag header value.
   * @var string
   */
  protected $ETag = '';

  /**
   * Stores the response modified date.
   * @var \DateTime
   */
  protected $modifiedDate = null;

  /**
   * Enables or disables caching.
   *
   * @param bool $value True to enable caching.
   *
   * @return void
   */
  public function setCacheable(bool $value) {
    $this->cacheable = $value;
  }

  /**
   * Returns the current cache enabled/disabled flag value.
   *
   * @return boolean
   */
  public function getCacheable() : bool {
    return $this->cacheable;
  }

  /**
   * Sets the cache revalidation flag.
   *
   * @param bool $value True to use revalidation.
   *
   * @return void
   */
  public function setRevalidate(bool $value) {
    $this->revalidate = $value;
  }

  /**
   * Returns the current cache revalidation flag value.
   *
   * @return boolean
   */
  public function getRevalidate() : bool {
    return $this->revalidate;
  }

  /**
   * Sets the response privacy to private or public.
   *
   * @param bool $value True to set cache as private.
   *
   * @return void
   */
  public function setPrivate(bool $value) {
    $this->private = $value;
  }

  /**
   * Returns the current response privacy flag value.
   *
   * @return boolean
   */
  public function getPrivate() : bool {
    return $this->private;
  }

  /**
   * Sets the response max age (expiration period).
   *
   * @param float $value The expiration period.
   * @param int   $unit  One of the AGE_* constants indicating in which time unit the period was given.
   *
   * @return void
   */
  public function setAge(float $value, int $unit = self::AGE_SECONDS) {
    switch ($unit) {
      case self::AGE_MINUTES:
        $this->age = $value * 60;
        break;
      case self::AGE_HOURS:
        $this->age = $value * 60 * 60;
        break;
      case self::AGE_DAYS:
        $this->age = $value * 60 * 60 * 24;
        break;
      default:
        $this->age = $value;
    }
  }

  /**
   * Returns the current response max age (expiration period) value.
   *
   * @param int $unit One of the AGE_* constants indicating in which time unit the result will be returned.
   *
   * @return float The current expiration period.
   */
  public function getAge(int $unit = self::AGE_SECONDS) : float {
    switch ($unit) {
      default:
        return $this->age;
      case self::AGE_MINUTES:
        return $this->age / 60;
      case self::AGE_HOURS:
        return $this->age / 60 / 60;
      case self::AGE_DAYS:
        return $this->age / 60 / 60 / 24;
    }
  }

  /**
   * Sets the response ETag value.
   *
   * @param string $value ETag value.
   *
   * @return void
   */
  public function setETag(string $value) {
    $this->ETag = $value;
  }

  /**
   * Returns the current response ETag value.
   *
   * @return string
   */
  public function getETag() : string {
    return $this->ETag;
  }

  /**
   * Sets the response modified date.
   *
   * @param string|\DateTime $value A date as a string or an object.
   *
   * @return void
   */
  public function setModifiedDate($value) {
    if (is_string($value)) {
      try {
        $this->modifiedDate = new \DateTime($value);
      } catch (\Exception $ex) {
        $this->modifiedDate = null;
      }
    } elseif (is_a($value, '\\DateTime')) {
      $this->modifiedDate = $value;
    } else {
      $this->modifiedDate = null;
    }
  }

  /**
   * Returns the current response modified date.
   *
   * @return \DateTime A DateTime object.
   */
  public function getModifiedDate() : \DateTime {
    return $this->modifiedDate;
  }

  /**
   * Outputs HTTP response headers.
   *
   * @return void
   */
  protected function outputHeaders() {
    $controlFlags = [];
    if (!$this->cacheable) {
      $controlFlags[] = 'no-store';
      $controlFlags[] = 'no-cache';
    } else {
      if ($this->revalidate) {
        $controlFlags[] = 'no-cache';
        $controlFlags[] = 'must-revalidate';
      }
      $controlFlags[] = $this->private ? 'private' : 'public';
      if ($this->age > 0) {
        $controlFlags[] = 'max-age=' . (int)round($this->age);
      }
      if (!empty($this->ETag)) {
        $this->setHeader('ETag', $this->ETag);
      }
      if (!is_null($this->modifiedDate)) {
        $this->setHeader('Last-Modified', $this->httpUtils->formatDateTime($this->modifiedDate));
      }
    }
    if (!empty($controlFlags)) {
      $this->setHeader('Cache-Control', implode(', ', $controlFlags));
    }
    parent::outputHeaders();
  }

}
