<?php

/**
 * UploadedFile class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\FileSystem;

use Core\FileSystem\File;
use Core\Exceptions\FileSystemException;

/**
 * Class for operations with files in a OO way with some security checks and specific methods for uploaded files.
 *
 * @package Core
 */
class UploadedFile extends File {

  /**
   * Stores the uploaded file flag.
   * @var boolean
   */
  protected $uploadedFile = false;

  /**
   * Stores the original filename as sent by the client.
   * @var string
   */
  protected $originalName = '';

  /**
   * Stores the original file MIME type as sent by the client.
   * @var string
   */
  protected $originalMIMEType = 'application/octet-stream';

  /**
   * Resets (recreates) the internal SplFileInfo.
   *
   * @param string $filename File name
   *
   * @return void
   */
  protected function resetInternalFileObject(string $filename) {
    parent::resetInternalFileObject($filename);

    if (!is_null($this->splFile)) {
      $this->uploadedFile = is_uploaded_file($filename);
    } else {
      $this->uploadedFile = false;
    }
  }

  /**
   * Constructor.
   *
   * @param array $fileinfo The uploaded file info as returned by the $_FILES superglobal.
   */
  public function __construct(array $fileinfo) {
    switch ($fileinfo['error']) {
      case UPLOAD_ERR_INI_SIZE:
        throw FileSystemException::createSelf(
          'The uploaded file exceeds the upload_max_filesize directive.',
          $fileinfo['error']
        );
      case UPLOAD_ERR_FORM_SIZE:
        throw FileSystemException::createSelf(
          'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
          $fileinfo['error']
        );
      case UPLOAD_ERR_PARTIAL:
        throw FileSystemException::createSelf('The uploaded file was only partially uploaded.', $fileinfo['error']);
      case UPLOAD_ERR_NO_FILE:
        throw FileSystemException::createSelf('No file was uploaded.', $fileinfo['error']);
      case UPLOAD_ERR_NO_TMP_DIR:
        throw FileSystemException::createSelf('Missing a temporary folder for uploads.', $fileinfo['error']);
      case UPLOAD_ERR_CANT_WRITE:
        throw FileSystemException::createSelf('Failed to write uploaded file to disk.', $fileinfo['error']);
      case UPLOAD_ERR_EXTENSION:
        throw FileSystemException::createSelf('An extension stopped the file upload.', $fileinfo['error']);
    }

    parent::__construct($fileinfo['tmp_name']);

    $this->originalName = $fileinfo['name'];
    $this->originalMIMEType = $fileinfo['type'];
  }

  /**
   * Gets only the original name of the file, optionally including it's extension.
   *
   * @param bool $extension True if you want the extension.
   *
   * @return string The original file name.
   */
  public function originalName(bool $extension = true) : string {
    if ($extension) {
      return $this->originalName;
    } else {
      return pathinfo($this->originalName, PATHINFO_FILENAME);
    }
  }

  /**
   * Gets the original extension of the file.
   *
   * @return string The original file extension or an empty string if the file has no extension.
   */
  public function originalExtension() : string {
    $ext = pathinfo($this->originalName, PATHINFO_EXTENSION);
    if (is_null($ext)) {
      $ext = '';
    }
    return $ext;
  }

  /**
   * Gets the original file MIME type. This information is generally provided by the browser and can be manipulated,
   * therefore you should not trust it.
   *
   * @return string The original file MIME type.
   */
  public function originalMIMEType() : string {
    return $this->originalMIMEType;
  }

  /**
   * Checks if the file is a uploaded file.
   *
   * @return bool True if the file is a upload file, False otherwise.
   */
  public function isUploadedFile() : bool {
    return $this->uploadedFile;
  }

  /**
   * Copy the file to another location, but only if it is an upload file.
   *
   * @param string $destination Destination.
   *
   * @return boolean|\Core\Files\File A File object of the new file or false on failure.
   */
  public function copyTo(string $destination) {
    if (!$this->isUploadedFile()) {
      return false;
    }
    return parent::copyTo($destination);
  }

  /**
   * Move the file to another location, but only if it is an upload file.
   *
   * @param type $destination Destination.
   *
   * @return bool True on success.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function moveTo(string $destination) : bool {
    if (!$this->isUploadedFile()) {
      return false;
    }
    return parent::moveTo($destination);
  }

  /**
   * Rename the file, optionally changing it's extension, but only if it is an upload file.
   *
   * @param string         $newname   New name of the file, without extension.
   * @param string|boolean $extension The new extension as a string or False to not change it.
   *
   * @return bool True on success.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function renameMe(string $newname, bool $extension = false) : bool {
    if (!$this->isUploadedFile()) {
      return false;
    }
    return parent::renameMe($newname, $extension);
  }

  /**
   * Delete the file, but only if it is an upload file. Pratically all methods will return errors after calling deleteMe.
   *
   * @return bool True on success.
   * @throws \Core\Exceptions\FileSystemException
   */
  public function deleteMe() : bool {
    if (!$this->isUploadedFile()) {
      return false;
    }
    return parent::deleteMe();
  }
}
