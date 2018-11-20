<?php

/**
 * FileTemplateSource class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace TemplateEngine\TemplateSources;

use TemplateEngine\TemplateSources\TemplateSource;
use Core\FileSystem\File;
use TemplateEngine\Exceptions\TemplateException;

/**
 * Template source class that allows loading a template from a file.
 *
 * @package TemplateEngine
 */
class FileTemplateSource extends TemplateSource {

  /**
   * Stores a reference to the current template file.
   * @var \Core\FileSystem\File
   */
  protected $file = null;

  /**
   * Constructor.
   *
   * @param string|\Core\FileSystem\File $file The file name or a file object.
   */
  public function __construct($file = null) {
    $this->setFile($file);
  }

  /**
   * Sets the template file.
   *
   * @param string|\Core\FileSystem\File $file The file name or a file object.
   *
   * @return void
   */
  public function setFile($file) {
    if (is_string($file)) {
      $file = new File($file);
    }
    if (is_a($file, 'Core\\FileSystem\\File')) {
      $this->file = $file;
    } else {
      $this->file = null;
    }
  }

  /**
   * Returns the current template file.
   *
   * @return \Core\FileSystem\File
   */
  public function getFile() : \Core\FileSystem\File {
    return $this->file;
  }

  /**
   * Returns the template as a string.
   *
   * @return string The template string.
   * @throws \TemplateEngine\Exceptions\TemplateSourceException
   */
  public function getTemplate() : string {
    if (is_null($this->file)) {
      return '';
    }
    $closed = !$this->file->isOpen();
    try {
      if ($closed) {
        $this->file->open(File::MODE_READ);
      }
      $template = $this->file->readAll();
      if ($template === false) {
        $template = '';
      }
      if ($closed) {
        $this->file->close();
      }
      return $template;
    } catch (\Exception $ex) {
      throw TemplateException::create('TemplateSourceException', 'Failed to load template file', 0, $ex);
    }
  }

}
