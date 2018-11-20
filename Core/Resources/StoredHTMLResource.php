<?php

/**
 * StoredHTMLResource class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Resources;

use Core\Application\ResponseStorage;
use Core\Resources\GeneratedHTMLResource;

/**
 * Resource class intended to respond with stored (server cached) HTML pages.
 *
 * @package Core
 */
class StoredHTMLResource extends GeneratedHTMLResource {

  /**
   * Stores a reference to a ResponseStorage class instance. Injection target.
   * @var \Core\Application\ResponseStorage
   */
  public $responseStorage = null;

  /**
   * The expired content threshold. For this class it is the maximum number of seconds that the content will be kept in
   * storage before being built again.
   *
   * @var int
   */
  protected $expiredThreshold = 60 * 60 * 24;

  /**
   * Determines if the request's query string will be included or not in the storage identifier alongside the URI. Be careful
   * as it can be used to fill up your content storage (most likely a database) with unwanted or duplicated data.
   *
   * @var bool
   */
  protected $includeQueryString = true;

  /**
   * Returns the URI of this Resource.
   *
   * @return string
   */
  protected function getURI() : string {
    if ('' == $uri = rtrim($this->request->getURI($this->includeQueryString), '/')) {
      $uri = '/';
    }
    return $uri;
  }

  /**
   * Gets the document ETag.
   *
   * @return string|null Returns an ETag or null if an ETag is not available.
   */
  protected function getDocumentETag() {
    return (is_null($this->documentETag) ? parent::getDocumentETag() : $this->documentETag);
  }

  /**
   * Gets the document modified date.
   *
   * @return \DateTime|null Returns the modified date or null if a modified date is not available.
   */
  protected function getDocumentModifiedDate() {
    return (is_null($this->documentModifiedDate) ? new \DateTime() : $this->documentModifiedDate);
  }

  /**
   * Determines if the stored content is considered expired.
   *
   * @param \DateTime $storedModDate The modified date of the stored content.
   * @param string    $storedETag    The ETag of the stored content.
   *
   * @return bool True if the stored content is expired, False otherwise.
   */
  protected function storedContentExpired(\DateTime $storedModDate = null, string $storedETag = null) : bool {
    if (!is_null($storedModDate)) {
      $interval = (new \DateTime())->getTimestamp() - $storedModDate->getTimestamp();
      return $interval >= $this->expiredThreshold;
    }
    return false;
  }

  /**
   * Builds a new document and stores it if possible.
   *
   * @return void
   */
  protected function buildAndStoreNewDocument() {
    parent::buildDocument();

    if ($this->responseStorage instanceof ResponseStorage) {
      $extra = [];
      if (!is_null($this->documentETag)) {
        $extra['etag'] = $this->documentETag;
      }
      if (!is_null($this->documentModifiedDate)) {
        $extra['modified_date'] = $this->documentModifiedDate;
      }
      if (count($extra) == 0) {
        $extra = null;
      }
      $this->responseStorage->store($this->getURI(), $this->documentResult, $extra);
    }
  }

  /**
   * Builds the document and stores it or retrieves a previously stored copy if available.
   *
   * @return void
   */
  protected function buildDocument() {
    if (!($this->responseStorage instanceof ResponseStorage)) {
      parent::buildDocument();
      return;
    }

    $storedContent = '';
    $extra = [];

    if ($this->responseStorage->retrieve($this->getURI(), $storedContent, $extra)) {
      $storedETag = null;
      $storedModDate = null;

      if (isset($extra['etag']) && is_string($extra['etag'])) {
        $extra['etag'] = trim($extra['etag']);
        if ($extra['etag'] != '') {
          $storedETag = $extra['etag'];
        }
      }

      if (isset($extra['modified_date'])) {
        if (is_int($extra['modified_date'])) {
          $extra['modified_date'] = '@' . $extra['modified_date'];
        }
        if (is_string($extra['modified_date'])) {
          try {
            $storedModDate = new \DateTime($extra['modified_date']);
          } catch (\Exception $ex) {
            $storedModDate = null;
          }
        } elseif ($extra['modified_date'] instanceof \DateTime) {
          $storedModDate = $extra['modified_date'];
        }
      }

      if ($this->storedContentExpired($storedModDate, $storedETag)) {
        $this->responseStorage->remove($this->getURI());
        $this->buildAndStoreNewDocument();
      } else {
        $this->documentETag = $storedETag;
        $this->documentModifiedDate = $storedModDate;
        $this->documentResult = $storedContent;
      }
    } else {
      $this->buildAndStoreNewDocument();
    }
  }

}
