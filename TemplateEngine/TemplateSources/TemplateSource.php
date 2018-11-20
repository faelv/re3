<?php

/**
 * TemplateSource class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace TemplateEngine\TemplateSources;

use Core\Application\NamespaceFactory;
use TemplateEngine\Exceptions\TemplateSourceException;

/**
 * Base class for template sources.
 *
 * @package TemplateEngine
 */
abstract class TemplateSource extends NamespaceFactory {

  /**
   * Returns the template as a string.
   *
   * @return string The template string.
   */
  abstract public function getTemplate() : string;

  /**
   * Returns an instance of the specified class.
   *
   * @param string $class    Class name. If the class name starts with a \ (backslash), then it's considered a FQN,
   *                         otherwise it will be infered that the class belongs to the same namespace as the class in
   *                         which the create method was called, in this case a namespace will be automatically added to
   *                         the class parameter.
   * @param mixed  ...$extra Any number of parameters of any type. Passed to the class constructor in the same order.
   *
   * @return object A class instance
   * @throws \TemplateEngine\Exceptions\TemplateSourceException
   */
  public static function create(string $class, ...$extra) {
    try {
      return parent::create($class, ...$extra);
    } catch (\Exception $ex) {
      throw TemplateSourceException::createSelf($ex->getMessage(), $ex->getCode(), $ex);
    }
  }

}
