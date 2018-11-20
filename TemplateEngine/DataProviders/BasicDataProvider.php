<?php

/**
 * BasicDataProvider class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace TemplateEngine\DataProviders;

use TemplateEngine\DataProviders\DataProvider;

/**
 * A DataProvider with basic methods.
 *
 * @package TemplateEngine
 */
class BasicDataProvider extends DataProvider {

  /**
   * Stores tag names and values.
   * @var array
   */
  protected $data = [];

  /**
   * Stores references to chained DataProviders.
   * @var array
   */
  protected $chainedProviders = [];

  /**
   * Replaces some characters from a tag name with underlines. Allowed characters are letters ([a..zA..Z]),
   * numbers ([0..9]), underline (_), dot (.), backslash (\) and hyphen (-).
   *
   * @param string $tag The tag name
   *
   * @return string The sanitized tag name
   */
  protected function sanitizeTagName(string $tag) : string {
    return mb_ereg_replace('/[^\w\\\.\-]/', '_', $tag);
  }

  /**
   * Chains a DataProvider so if data corresponding to a certain tag doesn't exists in this DataProvider it will be
   * retrieved from the next chained one. You can chain more than one DataProvider. Be careful not to create infinite
   * loops.
   *
   * @param \TemplateEngine\DataProviders\DataProvider $dataProvider A DataProvider.
   *
   * @return void
   */
  public function chainProvider(DataProvider $dataProvider) {
    if ($dataProvider != $this && !in_array($dataProvider, $this->chainedProviders)) {
      $this->chainedProviders[] = $dataProvider;
    }
  }

  /**
   * Adds data to the provider.
   *
   * @param string        $tag  Tag name
   * @param string|object $data The data. Anything that can be represented as string, if an object is used,
   *                            the __toString method will be called, it is preferable to use a
   *                            \TemplateEngine\DataSources\DataSource descendant in such cases.
   *
   * @return void
   */
  public function addData(string $tag, $data) {
    $this->data[$this->sanitizeTagName($tag)] = $data;
  }

  /**
   * Adds data from an array to the provider.
   *
   * @param array $array The data array, where the keys will be used as tag names.
   *
   * @return void
   */
  public function addDataArray(array $array) {
    foreach ($array as $tag => $data) {
      $this->addData($tag, $data);
    }
  }

  /**
   * Gets a tag's corresponding data.
   *
   * @param string $tag Tag name
   *
   * @return string|boolean The data or False on failure
   */
  public function getData(string $tag) {
    if (!isset($this->data[$tag])) {
      foreach ($this->chainedProviders as $provider) {
        $data = $provider->getData($tag);
        if ($data !== false) {
          return $data;
        }
      }
      return false;
    }
    $data = $this->data[$tag];
    if (is_string($data)) {
      return $data;
    }
    try {
      return (string)$data;
    } catch (\Exception $ex) {
      return false;
    }
  }

}
