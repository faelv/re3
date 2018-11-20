<?php

/**
 * StringTemplateSource class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace TemplateEngine\TemplateSources;

use TemplateEngine\TemplateSources\TemplateSource;

/**
 * Basic template source that only allows to use a template stored in a string.
 *
 * @package TemplateEngine
 */
class StringTemplateSource extends TemplateSource {

  /**
   * Stores the template string.
   * @var string
   */
  protected $template = '';

  /**
   * Constructor.
   *
   * @param string $str The template string.
   */
  public function __construct(string $str = '') {
    $this->setString($str);
  }

  /**
   * Sets the template string.
   *
   * @param string $str    The template string.
   * @param bool   $append Set to True if you want to append to the current string or False to overwrite it.
   *
   * @return void
   */
  public function setString(string $str, bool $append = false) {
    if (!$append) {
      $this->template = $str;
    } else {
      $this->template .= $str;
    }
  }

  /**
   * Returns the current template string.
   *
   * @return string The template string.
   */
  public function getString() : string {
    return $this->template;
  }

  /**
   * Returns the template as a string.
   *
   * @return string The template string.
   */
  public function getTemplate() : string {
    return $this->getString();
  }

}
