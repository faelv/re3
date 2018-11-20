<?php

/**
 * DBCollectionDataProvider class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace TemplateEngine\DataProviders;

use TemplateEngine\DataProviders\JSONDataProvider;
use TemplateEngine\DataProviders\MultiSetDataProvider;

/**
 * A DataProvider for database collections.
 *
 * @package TemplateEngine
 */
class DBCollectionDataProvider extends JSONDataProvider implements MultiSetDataProvider {

  /**
   * Stores a reference to a database collection
   * @var \Database\Main\DatabaseCollection
   */
  protected $collection = null;

  /**
   * Stores the current object index
   * @var int
   */
  protected $objectIndex = -1;

  /**
   * Constructor.
   *
   * @param \Database\Main\DatabaseCollection $collection A database collection
   */
  public function __construct(\Database\Main\DatabaseCollection $collection) {
    parent::__construct();
    $this->collection = $collection;
    $this->rewind();
  }

  /**
   * Goes back to the first object in the collection.
   *
   * @return void
   */
  public function rewind() {
    $this->objectIndex = -1;
    $this->nextDataSet();
  }

  /**
   * Advances to the next object in the collection.
   *
   * @return bool True if this provider advanced to the next object in the collection False if it reached the end of
   * the collection.
   */
  public function nextDataSet(): bool {
    if ($this->objectIndex + 1 < count($this->collection)) {
      $this->objectIndex++;
      return true;
    }
    return false;
  }

  /**
   * Gets a tag's corresponding data.
   *
   * @param string $tag Tag name
   *
   * @return string|boolean The data or False on failure
   */
  public function getData(string $tag) {
    if ($this->objectIndex < 0) {
      return false;
    }

    $data = $this->collection->item($this->objectIndex)->$tag;
    if (is_null($data)) {
      $data = parent::getData($tag);
    }
    return $data;
  }

}
