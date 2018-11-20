<?php

/**
 * NamespaceFactory class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Application;

use Core\Exceptions\CoreException;

/**
 * Any class that extends this class becomes a factory for all it's namespace subclasses
 *
 * @package Core
 */
abstract class NamespaceFactory {

  /**
   * Dependency injector reference.
   * @var \Core\Application\DependencyInjector
   */
  public static $dependencyInjector = null;

  /**
   * Returns an instance of the specified class.
   *
   * @param string $class    Class name. If the class name starts with a \ (backslash), then it's considered a FQN,
   *                         otherwise it will be infered that the class belongs to the same namespace as the class in
   *                         which the create method was called, in this case a namespace will be automatically added
   *                         to the class parameter.
   * @param mixed  ...$extra Any number of parameters of any type. Passed to the class constructor in the same order.
   *
   * @return object A class instance
   * @throws \Core\Exceptions\FactoryException
   */
  public static function create(string $class, ...$extra) {
    if (substr($class, 0, 1) != '\\') {
      $namespace = static::class;
      $clsPos = strrpos($namespace, '\\');
      if ($clsPos !== false) {
        $namespace = substr($namespace, 0, $clsPos);
      }
      $class = $namespace . '\\' . $class;
    }
    if (class_exists($class, true)) {
      $instance = new $class(...$extra);
      if (!is_null(static::$dependencyInjector)) {
        static::$dependencyInjector->injectInto($instance);
      }
      return $instance;
    } else {
      throw CoreException::create('FactoryException', "Class $class does not exists", 0, null, $class);
    }
  }

  /**
   * Returns an instance of this class.
   *
   * @param mixed ...$extra Any number of parameters of any type. Passed to the class constructor in the same order.
   *
   * @return object A class instance
   * @throws \Core\Exceptions\FactoryException
   */
  public static function createSelf(...$extra) {
    return self::create('\\' . static::class, ...$extra);
  }
}
