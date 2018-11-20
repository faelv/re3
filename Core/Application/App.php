<?php

/**
 * App class.
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Application;

use Core\Application\Autoloader;
use Core\Application\Logger;
use Core\Application\Router;
use Core\Application\DependencyInjector;
use Core\Application\NamespaceFactory;
use Core\Exceptions\CoreException;

require __DIR__ . '/Autoloader.php';
require __DIR__ . '/Logger.php';
require __DIR__ . '/Router.php';
require __DIR__ . '/DependencyInjector.php';
require __DIR__ . '/NamespaceFactory.php';
require __DIR__ . '/Polyfills.php';

/**
 * Base class for apps.
 *
 * @package Core
 */
abstract class App {

  /**
   * Autoloader class instance
   * @var \Core\Application\Autoloader
   */
  protected $loader = null;

  /**
   * Router class instance
   * @var \Core\Application\Router
   */
  protected $router = null;

  /**
   * DependendyInjector class instance
   * @var \Core\Application\DependencyInjector
   */
  protected $dependencyInjector = null;

  /**
   * Event Logger class instance
   * @var \Core\Application\Logger
   */
  protected $logger = null;

  /**
   * Request received
   * @var \Core\Requests\Request
   */
  protected $request = null;

  /**
   * Route information
   * @var array
   */
  protected $route = false;

   /**
   * Resource class instance
   * @var \Core\Resources\Resource
   */
  protected $resource = null;

  /**
   * Response class instance
   * @var \Core\Responses\Response
   */
  protected $response = null;

  /**
   * Return true if the current PHP version is supported, false otherwise.
   * @return bool
   */
  private function isPHPVersionSupported() {
    return (bool)version_compare(PHP_VERSION, '7.0.0', '>=');
  }

  /**
   * Applies the default timezone or revert to UTC in case of error.
   *
   * @return void
   */
  private function applyDefaultTimezone() {
    $tzStr = $this->getDefaultTimezone();
    if (is_string($tzStr)) {
      try {
        $tzObj = new \DateTimeZone($tzStr);
        date_default_timezone_set($tzObj->getName());
      } catch (\Exception $ex) {
        date_default_timezone_set('UTC');
        trigger_error('An invalid timezone (' . $tzStr . ') was specified by this app. Using "UTC" instead.', E_USER_WARNING);
      }
    }
  }

  /**
   * Constructor. Throws a CoreException if the current PHP version is not supported
   *
   * @throws \Core\Exceptions\CoreException
   */
  final public function __construct() {
    if (!$this->isPHPVersionSupported()) {
      throw new CoreException(
        'This version of PHP ('. PHP_VERSION .') is not supported, version 7.0 or higher is necessary.'
      );
    }

    mb_internal_encoding('UTF-8');
    set_error_handler([$this, 'errorHandler']);
    set_exception_handler([$this, 'exceptionHandler']);
    register_shutdown_function([$this, 'shutdownHandler']);

    \Polyfills::define();

    $this->logger = $this->getLogger();

    $this->loader = $this->getLoader();
    $this->router = $this->getRouter();
    $this->dependencyInjector = $this->getDependencyInjector();

    NamespaceFactory::$dependencyInjector = $this->dependencyInjector;

    $this->setup();
    $this->applyDefaultTimezone();
  }

  /**
   * Returns the current Autoloader
   *
   * @return \Core\Application\Autoloader
   */
  protected function getLoader() {
    return new Autoloader();
  }

  /**
   * Returns the current Router
   *
   * @return \Core\Application\Router
   */
  protected function getRouter() {
    return new Router();
  }

  /**
   * Returns the current event Logger
   *
   * @return \Core\Application\Logger
   */
  protected function getLogger() {
    return new Logger();
  }

  /**
   * Returns the current DependencyInjector
   *
   * @return \Core\Application\DependencyInjector
   */
  protected function getDependencyInjector() {
    return new DependencyInjector();
  }

  /**
   * Returns the default timezone used by date and time methods or objects.
   *
   * @return string|null A valid timezone string or null to not set a default timezone.
   */
  protected function getDefaultTimezone() {
    return 'UTC';
  }

  /**
   * Called after the class instantiation. It's intended for setting up things like namespaces and routes.
   *
   * @return void
   */
  protected function setup() {
    $docRoot = dirname(__FILE__, 3);

    $this->loader->addNamespace('Core\\', $docRoot . '/Core');
    $this->loader->addNamespace('TemplateEngine\\', $docRoot . '/TemplateEngine');
    $this->loader->addNamespace('Database\\', $docRoot . '/Database');
    $this->loader->addNamespace('Applets\\', dirname($docRoot) . '/applets');

    $this->dependencyInjector->addInjectionSource(
      'CryptUtils', '\\Core\\Utils\\CryptUtils', $this->dependencyInjector::SOURCE_CLASS, true
    );
  }

  /**
   * Returns a Core\Requests\Request
   *
   * @return void
   */
  abstract protected function getRequest() : \Core\Requests\Request;

  /**
   * Returns a Core\Resources\Resource that will process the request.
   *
   * @return void
   */
  abstract protected function getResource() : \Core\Resources\Resource;

  /**
   * Returns a Core\Responses\Response, as result of the resource processing the request
   *
   * @return void
   */
  abstract protected function getResponse() : \Core\Responses\Response;

  /**
   * Returns an array with information about the selected route. The Router class is intended to be used here.
   *
   * @return void
   */
  abstract protected function getRoute() : array;

  /**
   * Outputs the response content (or body), if any.
   *
   * @return void
   */
  abstract protected function output();

  /**
   * Main method, gets the request, select a route, get a resource, get the response and outputs it's content.
   *
   * @return void
   */
  public function execute() {
    $this->request  = $this->getRequest();
    $this->route    = $this->getRoute();
    $this->resource = $this->getResource();
    $this->response = $this->getResponse();
    $this->output();
  }

  /**
   * Creates an instance of this app and calls the execute method.
   *
   * @return void
   */
  final public static function executeInstance() {
    (new static())->execute();
  }

  /**
   * Custom error handler.
   *
   * @param int    $level   The level of the error.
   * @param string $message The error message.
   * @param string $file    The file where the error was raised.
   * @param int    $line    The line number where the error was raised.
   *
   * @return void
   */
  final public function errorHandler($level, $message, $file, $line) {
    //skip the internal handler by returning true
    switch ($level) {
      //Errors that will not be converted to exceptions, just logged or displayed.
      case E_NOTICE:
      case E_USER_NOTICE:
      case E_DEPRECATED:
      case E_USER_DEPRECATED:
        $this->logger->log(Logger::EVENT_INFO, $message . PHP_EOL . 'FILE: ' . $file . ' - LINE: ' . $line);
        return true;

      case E_WARNING:
      case E_USER_WARNING:
        $this->logger->log(Logger::EVENT_WARNING, $message . PHP_EOL . 'FILE: ' . $file . ' - LINE: ' . $line);
        return true;

      //Errors that will be converted to exceptions
      case E_USER_ERROR:
      case E_STRICT:
      case E_RECOVERABLE_ERROR:
        throw new \ErrorException($message, 0, $level, $file, $line);

      default:
        return false;
    }
  }

  /**
   * Custom exception handler.
   *
   * @param Exception|Throwable|Error $exception The exception object or a Throwable|Error if PHP >= 7.0
   *
   * @return void
   */
  final public function exceptionHandler($exception) {
    if (is_a($exception, '\\Error') || is_a($exception, '\\ErrorException')) {
      $this->logger->log(Logger::EVENT_ERROR, $exception);
    } else {
      $this->logger->log(Logger::EVENT_WARNING, $exception);
    }
  }

  /**
   * Custom shutdown handler.
   *
   * @return void
   */
  final public function shutdownHandler() {
    $this->logger->outputOSD();
  }
}
