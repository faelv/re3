<?php

/**
 * DBObjectDataProvider class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace TemplateEngine\DataProviders;

use TemplateEngine\DataProviders\JSONDataProvider;

/**
 * A DataProvider for database objects.
 *
 * @package TemplateEngine
 */
class DBObjectDataProvider extends JSONDataProvider {

  /**
   * Stores a reference to a database object
   * @var \Database\Main\DatabaseObject
   */
  protected $object = null;

  /**
   * Constructor.
   *
   * @param \Database\Main\DatabaseCollection $object A database object
   */
  public function __construct(\Database\Main\DatabaseObject $object) {
    parent::__construct();
    $this->object = $object;
  }

  /**
   * Gets a tag's corresponding data.
   *
   * @param string $tag Tag name
   *
   * @return string|boolean The data or False on failure
   */
  public function getData(string $tag) {
    $data = is_null($this->object) ? null : $this->object->$tag;
    if (is_null($data)) {
      $data = parent::getData($tag);
    }
    return $data;
  }

}
