<?php

/**
 * JSONDataProvider class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace TemplateEngine\DataProviders;

use TemplateEngine\DataProviders\BasicDataProvider;
use Core\FileSystem\File;
use TemplateEngine\Exceptions\TemplateException;

/**
 * A DataProvider that works with JSON.
 *
 * @package TemplateEngine
 */
class JSONDataProvider extends BasicDataProvider {

  /**
   * Constructor.
   *
   * @param string|\Core\FileSystem\File $file The file name or a File object. Optional.
   * @param string                       $json The JSON string. Optional.
   */
  public function __construct($file = null, string $json = null) {
    if (!is_null($file)) {
      $this->addDataFromJSONFile($file);
    }
    if (!is_null($json)) {
      $this->addDataFromJSONString($json);
    }
  }

  /**
   * Adds data from a JSON string. Since the data is intended to be in a tag => value format, the object hierarchy
   * will be used to create a namespace for tag names, like foo.bar => value.
   *
   * @param string $json The JSON string.
   *
   * @return bool True on success.
   * @throws \TemplateEngine\Exceptions\DataProviderException
   */
  public function addDataFromJSONString(string $json) : bool {
    if (!is_string($json)) {
      return false;
    }

    $array = json_decode($json, true, 8);
    if (is_null($array)) {
      throw TemplateException::create('DataProviderException', 'Invalid JSON string');
    }
    if (empty($array)) {
      return false;
    }

    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveArrayIterator($array, \RecursiveArrayIterator::CHILD_ARRAYS_ONLY),
      \RecursiveIteratorIterator::SELF_FIRST
    );

    $tagNamespace = [];
    foreach ($iterator as $tag => $value) {
      if ($iterator->getDepth() < count($tagNamespace)) {
        array_pop($tagNamespace);
      }
      if (is_array($value)) {
        $tagNamespace[] = $tag;
      } else {
        if (count($tagNamespace) > 0) {
          $tag = '.' . $tag;
        }
        $this->addData(implode('.', $tagNamespace) . $tag, $value);
      }
    }

    return true;
  }

  /**
   * Adds data from a JSON file. The same as using addDataFromJSONString passing the file contents.
   *
   * @param string|\Core\FileSystem\File $file The file name or a File object.
   *
   * @return void
   * @throws \TemplateEngine\Exceptions\DataProviderException
   */
  public function addDataFromJSONFile($file) {
    if (is_string($file)) {
      $file = new File($file);
    }
    if (is_object($file) && $file instanceof \Core\FileSystem\File) {
      $closed = !$file->isOpen();
      try {
        if ($closed) {
          $file->open(File::MODE_READ);
        }
        $this->addDataFromJSONString($file->readAll());
        if ($closed) {
          $file->close();
        }
      } catch (\Exception $ex) {
        throw TemplateException::create('DataProviderException', 'Failed to add data from JSON file', 0, $ex);
      }
    }
  }

}
