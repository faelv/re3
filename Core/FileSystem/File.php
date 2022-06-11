<?php

/**
 * File class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\FileSystem;

use Core\FileSystem\FileSystemObject;
use Core\Exceptions\CoreException;

/**
 * Class for operations with files in a OO way.
 *
 * @package Core
 */
class File extends FileSystemObject {

  /**
   * Opens a file for reading only.
   */
  const MODE_READ = 0;

  /**
   * Opens a file for reading and writing.
   */
  const MODE_READ_WRITE = 1;

  /**
   * Opens a file for writing only, erasing all it's contents first.
   */
  const MODE_WRITE_ERASE = 2;

  /**
   * Opens a file for reading and writing, erasing all it's contents first.
   */
  const MODE_READ_WRITE_ERASE = 3;

  /**
   * Opens a file for writing only, putting the cursor at it's end.
   */
  const MODE_WRITE_APPEND = 4;

  /**
   * Opens a file for reading and writing, putting the cursor at it's end.
   */
  const MODE_READ_WRITE_APPEND = 5;

  /**
   * Creates and opens a file for writing only, placing the cursor at the beginning of the file. If the file already exists, it fails.
   */
  const MODE_CREATE = 6;

  /**
   * Creates and opens a file for reading and writing, placing the cursor at the beginning of the file. If the file already exists, it fails.
   */
  const MODE_READ_CREATE = 7;

  /**
   * Creates and opens the file for writing only. If the file does not exist, it is created. If it exists, places the cursor at the beginning of the file.
   */
  const MODE_LOOSE_CREATE = 8;

  /**
   * Creates and opens the file for reading and writing. If the file does not exist, it is created. If it exists, places the cursor at the beginning of the file.
   */
  const MODE_LOOSE_READ_CREATE = 9;

  /**
   * Shared lock (reader)
   */
  const LOCK_SHARED = LOCK_SH;

  /**
   * Exclusive lock (writer).
   */
  const LOCK_EXCLUSIVE = LOCK_EX;

  /**
   * Non-blocking operation while locking.
   */
  const LOCK_NON_BLOCKING = LOCK_NB;

  /**
   * Internal file object
   * @var SplFileObject
   */
  protected $splFile = null;

  /**
   * Defines if the file is open or not.
   * @var boolean
   */
  protected $openState = false;

  /**
   * Checks if a given file exists.
   *
   * @param string $filename Filename, complete including it's path.
   *
   * @return bool True if the file exists.
   */
  public static function exists(string $filename) : bool {
    return file_exists($filename);
  }

  /**
   * Changes the file mode.
   *
   * @param string $filename Filename, complete including it's path.
   * @param int    $mode     Permission mode.
   *
   * @return bool True on success.
   */
  public static function changeMode(string $filename, int $mode) : bool {
    return chmod($filename, $mode);
  }

  /**
   * Copies a file. If the destination file already exists, it will be overwritten.
   *
   * @param string $filename    The source file.
   * @param string $destination The destination file.
   *
   * @return bool True if success.
   */
  public static function copy(string $filename, string $destination) : bool {
    return copy($filename, $destination);
  }

  /**
   * Moves a file. If the destination file already exists, it will be overwritten.
   *
   * @param string $filename    The source file.
   * @param string $destination The destination file.
   *
   * @return bool True if success.
   */
  public static function move(string $filename, string $destination) : bool {
    return rename($filename, $destination);
  }

  /**
   * Deletes a file.
   *
   * @param string $filename The file.
   *
   * @return bool True if success.
   */
  public static function delete(string $filename) : bool {
    return unlink($filename);
  }

  /**
   * Renames a file, optionally changing it's extension. If a file with the same name and extension already exists, it
   * will be overwritten.
   *
   * @param string         $filename  The file.
   * @param string         $newname   New name of the file, without extension.
   * @param string|boolean $extension The new extension as a string or False to not change it.
   *
   * @return boolean|string The complete file path as a string if success, False otherwise.
   */
  public static function rename(string $filename, string $newname, bool $extension = false) {
    $info = pathinfo($filename);
    if (!isset($info['extension'])) {
      $info['extension'] = '';
    }
    if ($extension !== false) {
      $info['extension'] = $extension;
    }
    if (!isset($info['dirname'])) {
      $info['dirname'] = '';
    }
    $destination = empty($info['dirname']) ? '' : (DIRECTORY_SEPARATOR . $newname);
    if (!empty($info['extension'])) {
      $destination .= '.' . $info['extension'];
    }
    if (rename($filename, $destination)) {
      return $destination;
    } else {
      return false;
    }
  }

  /**
   * Resets (recreates) the internal SplFileInfo.
   *
   * @param string $filename File name
   *
   * @return void
   */
  protected function resetInternalFileObject(string $filename) {
    $this->openState = false;
    $this->splFile = null;
    if (!empty($filename)) {
      $this->splFile = new \SplFileInfo($filename);
    }
  }

  /**
   * Convenience method. If the file is not open throws an exception.
   *
   * @param string $opName Name of the operation.
   *
   * @return void
   * @throws \Core\Exceptions\FileSystemException
   */
  protected function checkFileClosedOperation(string $opName) {
    if (!$this->isOpen()) {
      throw CoreException::create('FileSystemException', "$opName cannot be performed on a closed file: {$this->splFile->getPathname()}");
    }
  }

  /**
   * Convenience method. If the file is open throws an exception.
   *
   * @param string $opName Name of the operation.
   *
   * @return void
   * @throws \Core\Exceptions\FileSystemException
   */
  protected function checkFileOpenOperation(string $opName) {
    if ($this->isOpen()) {
      throw CoreException::create('FileSystemException', "$opName cannot be performed on a open file: {$this->splFile->getPathname()}");
    }
  }

  /**
   * Constructor.
   *
   * @param string $filename The file name.
   */
  public function __construct(string $filename) {
    $this->resetInternalFileObject($filename);
  }

  /**
   * Opens the file with the specified access type. Some methods can only be performed while the file is open.
   *
   * @param int $mode One the MODE_* constants.
   *
   * @return bool True if success.
   */
  public function open(int $mode) : bool {
    if ($this->openState) {
      return false;
    }
    switch ($mode) {
      case self::MODE_READ_WRITE:
        $mode = 'r+b';
        break;
      case self::MODE_WRITE_ERASE:
        $mode = 'wb';
        break;
      case self::MODE_READ_WRITE_ERASE:
        $mode = 'w+b';
        break;
      case self::MODE_WRITE_APPEND:
        $mode = 'ab';
        break;
      case self::MODE_READ_WRITE_APPEND:
        $mode = 'a+b';
        break;
      case self::MODE_CREATE:
        $mode = 'xb';
        break;
      case self::MODE_READ_CREATE:
        $mode = 'x+b';
        break;
      case self::MODE_LOOSE_CREATE:
        $mode = 'cb';
        break;
      case self::MODE_LOOSE_READ_CREATE:
        $mode = 'c+b';
        break;
      default: //MODE_READ
        $mode = 'rb';
    }
    try {
      $this->splFile = $this->splFile->openFile($mode);
      $this->openState = true;
      return true;
    } catch (\Exception $ex) {
      return false;
    }
  }

  /**
   * Close the file. Some methods can only be performed while the file is closed.
   *
   * @return bool True if success.
   */
  public function close() : bool {
    $this->resetInternalFileObject($this->splFile->getPathname());
    return true;
  }

  /**
   * Checks if the the file is open.
   *
   * @return bool True if the file is open.
   */
  public function isOpen() : bool {
    return $this->openState;
  }

  /**
   * Checks if the file is readable.
   *
   * @return bool True if the file is readable.
   */
  public function isReadable() : bool {
    return $this->splFile->isReadable();
  }

  /**
   * Tells if this is a directory.
   *
   * @return boolean
   */
  public function isDirectory() : bool {
    return false;
  }

  /**
   * Tells if this is a file.
   *
   * @return boolean
   */
  public function isFile() : bool {
    return true;
  }

  /**
   * Checks if the file is writable.
   *
   * @return bool True if the file is writable.
   */
  public function isWritable() : bool {
    return $this->splFile->isWritable();
  }

  /**
   * Gets the size of the file in bytes.
   *
   * @return int|boolean The file size as an int or false on failure.
   */
  public function size() {
    try {
      return $this->splFile->getSize();
    } catch (\Exception $ex) {
      try {
        return array_get_if_set($this->splFile->fstat(), 'size', false);
      } catch (\Exception $ex) {
        return false;
      }
    }
  }

  /**
   * Gets the complete path to file, including it's name and extension.
   *
   * @return string The file path.
   */
  public function path() : string {
    return $this->splFile->getPathname();
  }

  /**
   * Gets the file directory, omitting any trailing slash.
   *
   * @return string The file directory.
   */
  public function directory() : string {
    return $this->splFile->getPath();
  }

  /**
   * Gets only the name of the file, optionally including it's extension.
   *
   * @param bool $extension True if you want the extension.
   *
   * @return string The file name.
   */
  public function name(bool $extension = true) : string {
    if ($extension) {
      return $this->splFile->getFilename();
    } else {
      return $this->splFile->getBasename('.' . $this->splFile->getExtension());
    }
  }

  /**
   * Gets the extension of the file.
   *
   * @return string The file extension or an empty string if the file has no extension.
   */
  public function extension() : string {
    return $this->splFile->getExtension();
  }

  /**
   * Gets the file last modified date and time.
   *
   * @return \DateTime The date.
   */
  public function modifiedDate() : \DateTime {
    $mtime = 0;
    try {
      $mtime = $this->splFile->getMTime();
    } catch (\Exception $ex) {
      $mtime = array_get_if_set($this->splFile->fstat(), 'mtime', $mtime);
    }
    return \DateTime::createFromFormat('U', $mtime);
  }

  /**
   * Reads a line from the file and advances to the next line.
   *
   * @return string|boolean The line as a string or false on failure.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function readLine() {
    $this->checkFileClosedOperation(__FUNCTION__);
    try {
      return $this->splFile->fgets();
    } catch (\Exception $ex) {
      return false;
    }
  }

  /**
   * Reads the given number of bytes from the file.
   *
   * @param int $length The number of bytes to read.
   *
   * @return string|boolean Returns the string read from the file or False on failure.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function read(int $length) {
    $this->checkFileClosedOperation(__FUNCTION__);
    if ($length > 0) {
      return $this->splFile->fread($length);
    } else {
      return '';
    }
  }

  /**
   * Reads the file contents.
   *
   * @return string|boolean Returns the file contents or False on failure.
   */
  public function readAll() {
    $this->checkFileClosedOperation(__FUNCTION__);
    $this->set(0);
    return $this->read($this->size());
  }

  /**
   * Writes to the file.
   *
   * @param string $data The string to be written to the file.
   *
   * @return int|boolean Returns the number of bytes written, or False on failure.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function write(string $data) {
    $this->checkFileClosedOperation(__FUNCTION__);
    $bytesWritten = $this->splFile->fwrite($data);
    return ($bytesWritten === 0) ? false : $bytesWritten;
  }

  /**
   * Forces a write of all buffered output to the file.
   *
   * @return bool True on success.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function flush() : bool {
    $this->checkFileClosedOperation(__FUNCTION__);
    return $this->splFile->fflush();
  }

  /**
   * Determine whether the end of file has been reached
   *
   * @return bool True if the file is at eof.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function eof() : bool {
    $this->checkFileClosedOperation(__FUNCTION__);
    return $this->splFile->eof();
  }

  /**
   * Moves the file pointer ahead by a number of bytes. Seeking past EOF is valid.
   *
   * @param int $bytes Number of bytes.
   *
   * @return bool True on success.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function seek(int $bytes) : bool {
    $this->checkFileClosedOperation(__FUNCTION__);
    return ($this->splFile->fseek($bytes, SEEK_CUR) === 0);
  }

  /**
   * Sets the file pointer at a specified position.
   *
   * @param type $position Position in bytes.
   *
   * @return bool True on success.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function set(int $position) : bool {
    $this->checkFileClosedOperation(__FUNCTION__);
    return ($this->splFile->fseek($position, SEEK_SET) === 0);
  }

  /**
   * Gets the current file pointer position.
   *
   * @return int|boolean The pointer position as an int or False on failure.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function position() {
    $this->checkFileClosedOperation(__FUNCTION__);
    return $this->splFile->ftell();
  }

  /**
   * Truncates the file to a given length.
   *
   * @param int $size Length (size) in bytes. If the size is larger than the file, it will be extended with null bytes.
   *                  If the size is smaller than the file, the excess data will be lost.
   *
   * @return bool True on success.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function truncate(int $size) : bool {
    $this->checkFileClosedOperation(__FUNCTION__);
    return $this->splFile->ftruncate($size);
  }

  /**
   * Erases all the contents of the file.
   *
   * @return bool True on success.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function erase() : bool {
    $this->checkFileClosedOperation(__FUNCTION__);
    return $this->splFile->ftruncate(0);
  }

  /**
   * Try to acquire a lock to the file.
   *
   * @param int $mode One the LOCK_* constants.
   * @param int $wouldBlock The optional third argument is set to 1 if the lock would block.
   *
   * @return bool True on success.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function lock(int $mode = self::LOCK_EXCLUSIVE, int &$wouldBlock = null) : bool {
    $this->checkFileClosedOperation(__FUNCTION__);
    return $this->splFile->flock($mode, $wouldBlock);
  }

  /**
   * Releases a file lock.
   *
   * @return bool True on success.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function unlock() : bool {
    $this->checkFileClosedOperation(__FUNCTION__);
    return $this->splFile->flock(LOCK_UN);
  }

  /**
   * Outputs all the file data, from the current pointer position, as a string.
   *
   * @return int The number of characters.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function output() : int {
    $this->checkFileClosedOperation(__FUNCTION__);
    return $this->splFile->fpassthru();
  }

  /**
   * Copy the file to another location.
   *
   * @param string $destination Destination.
   *
   * @return boolean|\Core\Files\File A File object of the new file or false on failure.
   */
  public function copyTo(string $destination) {
    if (self::copy($this->splFile->getPathname(), $destination)) {
      return new File($destination);
    } else {
      return false;
    }
  }

  /**
   * Move the file to another location.
   *
   * @param string $destination Destination.
   *
   * @return bool True on success.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function moveTo(string $destination) : bool {
    $this->checkFileOpenOperation(__FUNCTION__);
    $moved = self::move($this->splFile->getPathname(), $destination);
    if ($moved) {
      $this->resetInternalFileObject($destination);
    }
    return $moved;
  }

  /**
   * Rename the file, optionally changing it's extension.
   *
   * @param string         $newname   New name of the file, without extension.
   * @param string|boolean $extension The new extension as a string or False to not change it.
   *
   * @return bool True on success.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function renameMe(string $newname, bool $extension = false) : bool {
    $this->checkFileOpenOperation(__FUNCTION__);
    $renamed = self::rename($this->splFile->getPathname(), $newname, $extension);
    if ($renamed === false) {
      $this->resetInternalFileObject($renamed);
      return true;
    }
    return false;
  }

  /**
   * Changes the file mode.
   *
   * @param int $mode Permission mode.
   *
   * @return bool True on success.
   */
  public function changeMyMode(int $mode) : bool {
    return self::changeMode($this->splFile->getPathname(), $mode);
  }

  /**
   * Delete the file. Pratically all methods will return errors after calling deleteMe.
   *
   * @return bool True on success.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function deleteMe() : bool {
    $this->checkFileOpenOperation(__FUNCTION__);
    $filename = $this->splFile->getPathname();
    $this->resetInternalFileObject('');
    $deleted = self::delete($filename);
    if (!$deleted) {
      $this->resetInternalFileObject($filename);
    }
    return $deleted;
  }
}
