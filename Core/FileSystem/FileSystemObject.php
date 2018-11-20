<?php

/**
 * FileSystemObject class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\FileSystem;

/**
 * Base class for file system objects.
 *
 * @package Core
 */
abstract class FileSystemObject {

  /**
   * Tells if this is a directory.
   *
   * @return boolean
   */
  abstract public function isDirectory() : bool;

  /**
   * Tells if this is a file.
   *
   * @return boolean
   */
  abstract public function isFile() : bool;

}
