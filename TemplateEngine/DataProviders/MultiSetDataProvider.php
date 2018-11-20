<?php

/**
 * MultiSetDataProvider interface
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace TemplateEngine\DataProviders;

/**
 * Interface for data providers with more than one data set.
 *
 * @package TemplateEngine
 */
interface MultiSetDataProvider {

  /**
   * The data provider should advance to the next data set.
   *
   * @return bool True means that the provider advanced to the next data set, False means that there are no more
   * data sets.
   */
  public function nextDataSet() : bool;

}
