<?php

/**
 * DataSource class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace TemplateEngine\DataSources;

/**
 * Base class for data sources.
 *
 * @package TemplateEngine
 */
abstract class DataSource {

  /**
   * Returns a string representation of the data of this source.
   *
   * @return string
   */
  abstract public function __toString() : string;

}
