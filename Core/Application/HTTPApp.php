<?php

/**
 * HTTPApp class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Application;

use Core\Application\App;
use Core\Requests\Request;
use Core\Resources\Resource;

require_once __DIR__ . '/App.php';

/**
 * Base class for apps the handle the HTTP protocol.
 *
 * @package Core
 */
class HTTPApp extends App {

  /**
   * Request received
   * @var \Core\Requests\HTTPRequest
   */
  protected $request = null;

  /**
   * Called after the class instantiation. It's intended for setting up things like namespaces and routes.
   *
   * @return void
   */
  protected function setup() {
    parent::setup();

    $this->router->setInvalidRouteResource('NotFoundResource');

    $this->dependencyInjector->addInjectionSource(
      'HTTPUtils', '\\Core\\Utils\\HTTPUtils', $this->dependencyInjector::SOURCE_CLASS, true
    );

    $this->dependencyInjector->addInjectionSet('Core\\Application\\Session', ['cryptUtils' => 'CryptUtils']);
    $this->dependencyInjector->addInjectionSet('Core\\Responses\\HTTPResponse', ['httpUtils' => 'HTTPUtils']);
    $this->dependencyInjector->addInjectionSet('Core\\Resources\\HTTPResource', ['httpUtils' => 'HTTPUtils']);
  }

  /**
   * Always returns an HTTPRequest
   *
   * @return Core\Requests\HTTPRequest
   */
  protected function getRequest() : \Core\Requests\Request {
    return Request::create('HTTPRequest');
  }

  /**
   * Gets a route from the router by the request's URI
   *
   * @return array Route information.
   */
  protected function getRoute() : array {
    return $this->router->getRoute($this->request->getURI());
  }

  /**
   * Returns an instance of a resource class obtained from the route.
   *
   * @return Core\Resources\Resource
   */
  protected function getResource() : \Core\Resources\Resource {
    return Resource::create($this->route['resource_class']);
  }

  /**
   * Returns an instance of a response class obtained from the resource. Also sets the URI params of the request.
   *
   * @return Core\Responses\Response
   */
  protected function getResponse() : \Core\Responses\Response {
    foreach ($this->route['uri_params'] as $name => $value) {
      $this->request->setURIData($name, $value);
    }
    return $this->resource->getResponse($this->request);
  }

  /**
   * Outputs the response.
   *
   * @return void
   */
  protected function output() {
    $this->response->output();
  }
}
