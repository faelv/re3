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
    $etag = $this->request->getHeader('HTTP_IF_NONE_MATCH', null);
    if (is_string($etag)) {
      $etag = explode(',', $etag);
      for ($i = 0; $i < count($etag); $i++) {
        $etag[$i] = trim($etag[$i], '\'" ');
      }
      return $etag;
    } else {
      return [];
    }
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
   * Checks if an ETag matches the ones of the request. If true, then the cache is considered fresh. Only one etag needs
   * to match.
   *
   * @param string $etag The ETag value.
   *
   * @return bool True if it matches.
   */
  protected function ETagIsFresh(string $etag) : bool {
    $reqETags = $this->getRequestETag();
    foreach ($reqETags as $value) {
      if ($value == $etag) {
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
