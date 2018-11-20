<?php

/**
 * Autoloader class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Application;

/**
 * Responsable for autoloading other classes
 * Based on the PSR-4 Autoloader class example
 *
 * @package Core
 * @see http://www.php-fig.org/psr/psr-4/examples/
 */
class Autoloader {

  /**
   * Stores namespace prefixes and theis base dirs
   * @var array
   */
  protected $prefixes = [];

  /**
   * Constructor. Throws a LogicException if the autoloader register fails.
   *
   * @throws \LogicException
   */
  public function __construct() {
    if (!spl_autoload_register([$this, 'autoload'])) {
      throw new \LogicException('Autoloader could not be registered');
    }
  }

  /**
   * Autoload method registered by this class. Automatically requires files.
   *
   * @param string $class Fully qualified class name.
   * @return bool True if a file containing the class was found.
   */
  protected function autoload(string $class) : bool {
    $prefix = $class;
    while (false !== $pos = strrpos($prefix, '\\')) {
      $prefix = substr($class, 0, $pos + 1);
      $relativeClass = substr($class, $pos + 1);
      if (isset($this->prefixes[$prefix])) {
        $baseDir = $this->prefixes[$prefix];
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
          require $file;
          return true;
        }
      }
      $prefix = rtrim($prefix, '\\');
    }
    return false;
  }

  /**
   * Add namescape to file location mappings.
   *
   * @param string $prefix  The namscape prefix.
   * @param string $baseDir The base directory for all files of that prefix.
   *
   * @return void
   */
  public function addNamespace(string $prefix, string $baseDir) {
    $prefix = trim($prefix, '\\') . '\\';
    $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';
    $this->prefixes[$prefix] = $baseDir;
  }
}
