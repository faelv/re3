<?php

/**
 * GeneratedHTMLResource class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Resources;

use Core\Resources\CacheableResource;
use Core\Responses\Response;
use TemplateEngine\Main\HTMLDocument;

/**
 * Resource class intended to respond with dynamically generated HTML pages.
 *
 * @package Core
 */
class GeneratedHTMLResource extends CacheableResource {

  /**
   * Stores a template HTMLDocument object.
   * @var \TemplateEngine\Main\HTMLDocument
   */
  protected $document;

  /**
   * Stores the result of the document's build.
   * @var string
   */
  protected $documentResult = '';

  /**
   * Document ETag. Null if not available.
   * @var string
   */
  protected $documentETag = null;

  /**
   * Document modified date. Null if not available.
   * @var \DateTime
   */
  protected $documentModifiedDate = null;

  /**
   * Minify flag, set to True to minify the document result. Be careful, minification is a intensive process and still
   * experimental.
   * @var type
   */
  protected $minify = false;

  /**
   * If the Content-Length header should be automatically calculated and added to the response.
   *
   * @var bool
   */
  protected $outputContentLength = false;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->document = new HTMLDocument();
  }

  /**
   * Gets the document ETag.
   *
   * @return string|null Returns an ETag or null if an ETag is not available.
   */
  protected function getDocumentETag() {
    return $this->httpUtils->getETagHash($this->documentResult);
  }

  /**
   * Gets the document modified date.
   *
   * @return \DateTime|null Returns the modified date or null if a modified date is not available.
   */
  protected function getDocumentModifiedDate() {
    return null;
  }

  /**
   * Returns a response for requests made with the GET method.
   *
   * @return \Core\Responses\Response
   */
  protected function GETMethodResponse() : \Core\Responses\Response {
    $this->buildDocument();
    if ($this->cacheIsFresh($this->documentETag, $this->documentModifiedDate)) {
      $response = Response::create('NotModifiedResponse');
    } else {
      $response = Response::create('DynamicResponse');
      $response->setContent($this->documentResult);
      if (!is_null($this->documentETag)) {
        $response->setETag($this->documentETag);
      }
      if (!is_null($this->documentModifiedDate)) {
        $response->setModifiedDate($this->documentModifiedDate);
      }
    }
    if ($this->outputContentLength) {
      $this->setHeader('Content-Length', mb_strlen($this->documentResult));
    }
    $response->setHeader('Content-Type', $this->httpUtils->MIMETypeFromExtension('html'));
    return $response;
  }

  /**
   * Intended to setup the document's template source and data provider.
   *
   * <code>
   * $this->document->setTemplateSource(...);
   * $this->document->setDataProvider(...);
   * $this->document->addElementToHead(...);
   * </code>
   *
   * @return void
   */
  protected function setupDocument() {
    //placeholder for descendants
  }

  /**
   * Builds the document.
   *
   * @return void
   */
  protected function buildDocument() {
    $this->setupDocument();
    $this->documentResult = $this->document->build();

    if ($this->minify) {
      //TODO: use a better regex
      $this->documentResult = preg_replace('/<!--[^>]*-->|\/\*[^\/]*\*\//', '', $this->documentResult);
      $this->documentResult = preg_replace('/>\s+</', '><', $this->documentResult);
      $this->documentResult = preg_replace('/\s+((")\s{2,}(?=\2)\2)|\s\s+/', ' ', $this->documentResult);
      $this->documentResult = str_replace('} this', '}; this', $this->documentResult);
    }

    $this->documentETag = $this->getDocumentETag();
    $this->documentModifiedDate = $this->getDocumentModifiedDate();
  }

}
