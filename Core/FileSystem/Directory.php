<?php

/**
 * Directory class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\FileSystem;

use Core\FileSystem\FileSystemObject;
use Core\FileSystem\File;

/**
 * Class for operations with directories in a OO way.
 *
 * @package Core
 */
class Directory extends FileSystemObject implements \Iterator, \Countable {

  /**
   * Stores the path to this directory.
   * @var string
   */
  protected $path = '';

  /**
   * Stores a handle to this directory.
   * @var resource
   */
  protected $handle = false;

  /**
   * Stores the contents (items) of this directory.
   * @var array
   */
  protected $contents = [];

  /**
   * Stores the iterator key for the iterator interface.
   * @var int
   */
  protected $iteratorKey = -1;

  /**
   * Checks if a given directory exists.
   *
   * @param string $path Path to directory.
   *
   * @return bool True if the directory exists.
   */
  public static function exists(string $path) : bool {
    return file_exists($path);
  }

  /**
   * Creates a directory, including all missing directories in the path, if possible.
   *
   * @param string $path Path to directory.
   * @param int    $mode Permissions. This parameter is ignored on Windows.
   *
   * @return bool True on success.
   */
  public static function create(string $path, int $mode = 0755) : bool {
    return mkdir($path, $mode, true);
  }

  /**
   * Deletes an <b>empty</b> directory.
   *
   * @param string $path Path to directory
   *
   * @return bool True on success.
   */
  public static function delete(string $path) : bool {
    return rmdir($path);
  }

  /**
   * Constructor.
   *
   * @param string $path Path to directory
   */
  public function __construct(string $path) {
    $this->path = $path;
  }

  /**
   * Gets the directory complete path.
   *
   * @return string Path to directory.
   */
  public function path() : string {
    return $this->path;
  }

  /**
   * Gets the directoty name only.
   *
   * @return string Directory name.
   */
  public function name() : string {
    return basename($this->path);
  }

  /**
   * Updates the list of this directory contents. There is no need to call open before refresh.
   *
   * @return bool True on success.
   */
  public function refresh() : bool {
    for ($i = 0; $i < count($this->contents); $i++) {
      $this->contents[$i] = null; //GC hint, just a bit paranoid
    }
    $this->contents = [];
    $this->handle = false;
    return $this->open();
  }

  /**
   * Gets the contents of this directories.
   *
   * @return bool True on success.
   */
  public function open() : bool {
    if ($this->handle !== false) {
      return false;
    }
    $this->handle = opendir($this->path);
    if (!$this->handle) {
      return false;
    }
    try {
      while (false !== ($entry = readdir($this->handle))) {
        if ($entry == '.' || $entry == '..') {
          continue;
        }
        $entryType = filetype($entry);
        if ($entryType == 'dir') {
          $this->contents[] = new Directory($entry);
        } elseif ($entryType == 'file') {
          $this->contents[] = new File($entry);
        }
      }
    } finally {
      closedir($this->handle);
    }
  }

  /**
   * Returns an item from this directory.
   *
   * @param int $index Index of the item.
   *
   * @return \Core\FileSystem\FileSystemObject
   */
  public function item(int $index) : \Core\FileSystem\FileSystemObject {
    if ($index < 0 || $index >= $this->count()) {
      throw new \LogicException('Index "' . $index . '" is out of bounds');
    }
    return $this->contents[$index];
  }

  /**
   * Tells if this is a directory.
   *
   * @return boolean
   */
  public function isDirectory() : bool {
    return true;
  }

  /**
   * Tells if this is a file.
   *
   * @return boolean
   */
  public function isFile() : bool {
    return false;
  }

  /**
   * Creates this directory on the file system.
   *
   * @param int $mode Permissions. This parameter is ignored by Windows.
   *
   * @return bool True on success.
   */
  public function createMe(int $mode = 0755) : bool {
    return self::create($this->path(), $mode);
  }

  /**
   * Deletes a directory and all its contents, recursively.
   *
   * @param \Core\FileSystem\Directory $dir A directory object.
   *
   * @return bool True on success.
   */
  protected function deleteDir(\Core\FileSystem\Directory $dir) : bool {
    $dir->refresh();
    foreach ($dir as $item) {
      if ($item->isFile()) {
        $item->close();
        $item->deleteMe();
      } elseif ($item->isDir()) {
        $this->deleteDir($item);
      }
    }
    return self::delete($dir->path());
  }


  /**
   * Deletes this directory and all of its contents.
   *
   * @return bool True on success;
   */
  public function deleteMe() : bool {
    return $this->deleteDir($this);
  }

  //Below are interface methods

  /**
   * Returns the number of items in this directory. You nedd to open or refresh the directory to gets its contents.
   *
   * @return int Number of items.
   */
  public function count() : int {
    return count($this->contents);
  }

  /**
   * Interface method. Returns the current iterator item.
   *
   * @return \Core\FileSystem\FileSystemObject
   */
  public function current() : \Core\FileSystem\FileSystemObject {
    return ($this->iteratorKey >= 0) ? $this->contents[$this->iteratorKey] : null;
  }

  /**
   * Interface method. Returns the current iterator key.
   *
   * @return int
   */
  public function key() : int {
    return ($this->iteratorKey >= 0) ? $this->iteratorKey : null;
  }

  /**
   * Interface method. Advances the iterator key.
   *
   * @return void
   */
  public function next() {
    if ($this->iteratorKey >= 0) {
      $this->iteratorKey++;
    }
  }

  /**
   * Interface method. Sets the iterator key back to beginning.
   *
   * @return void
   */
  public function rewind() {
    $this->iteratorKey = ($this->count() > 0) ? 0 : -1;
  }

  /**
   * Interface method. Tells if the current iterator key is valid.
   *
   * @return bool True if valid.
   */
  public function valid() : bool {
    return ($this->iteratorKey >= 0 && $this->iteratorKey < $this->count());
  }

}
