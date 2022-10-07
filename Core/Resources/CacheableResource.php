<?php

/**
 * CacheableResource class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Resources;

use Core\Resources\HTTPResource;

/**
 * Resource class with cache related methods.
 *
 * @package Core
 */
class CacheableResource extends HTTPResource {

  /**
   * Returns the request's conditional header for ETags (If-None-Match), if present.
   *
   * @return array An array of etag strings.
   */
  protected function getRequestETag() : array {
    $header = $this->request->getHeader('HTTP_IF_NONE_MATCH', null);
    $etags = [];

    if (is_string($header)) {
      $etags = preg_split('/(?<=(?:"|\'))\s*,|,\s*(?=(?:"|\'))/', $header, -1, PREG_SPLIT_NO_EMPTY);
      if (!is_array($etags)) {
        $etags = [];
      } else {
        $etags = array_map('trim', $etags);
      }
    }

    return $etags;
  }

  /**
   * Returns the request's conditional header for modification date (If-Modified-Since), if present.
   *
   * @return \DateTime|null A DataTime object or Null if not available.
   */
  protected function getRequestModifiedSince() {
    $date = $this->request->getHeader('HTTP_IF_MODIFIED_SINCE', null);
    if (!is_null($date)) {
      return $this->httpUtils->dateTimeFromString($date);
    } else {
      return null;
    }
  }

  /**
   * Checks if an ETag is an weak ETag (starts with W/).
   *
   * @param string $etag The ETag value.
   *
   * @return bool True if it's a weak ETag, false otherwise.
   */
  protected function ETagIsWeak(string $etag) : bool {
    return str_starts_with($etag, 'W/');
  }

  /**
   * Checks if an ETag matches the ones of the request using strong comparison. If true, then the cache is considered fresh. Only one etag needs
   * to match.
   *
   * @param string $etag The ETag value.
   *
   * @return bool True if it matches.
   */
  protected function ETagIsFresh(string $etag) : bool {
    if ($this->ETagIsWeak($etag)) {
      return false;
    }

    $reqETags = $this->getRequestETag();

    foreach ($reqETags as $value) {
      if ($value === $etag && !$this->ETagIsWeak($value)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Checks a date against the request modified since date to determine if the cache is considered fresh.
   *
   * @param \DateTime $modifiedDate A DateTime object.
   *
   * @return bool True if the parameter's date is not greater than the request's one.
   */
  protected function modifiedDateIsFresh(\DateTime $modifiedDate) : bool {
    $reqMS = $this->getRequestModifiedSince();
    if (!is_null($reqMS)) {
      return $reqMS >= $modifiedDate;
    }
    return false;
  }

  /**
   * Checks if the cache is fresh by using the request's Etags or modified date.
   *
   * @param string    $etag         Etag value. Optional.
   * @param \DateTime $modifiedDate A DateTime object. Optional.
   *
   * @return bool True if at least one of two are considered fresh.
   */
  protected function cacheIsFresh(string $etag = null, \DateTime $modifiedDate = null) : bool {
    $etagFresh = is_null($etag) ? false : $this->ETagIsFresh($etag);
    $dateFresh = is_null($modifiedDate) ? false : $this->modifiedDateIsFresh($modifiedDate);
    return $etagFresh || $dateFresh;
  }

}
