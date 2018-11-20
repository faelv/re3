<?php

/**
 * HTTPResource class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Resources;

use Core\Resources\Resource;
use Core\Responses\Response;
use Core\Exceptions\CoreException;
use Core\Application\MagicInjection;

/**
 * Base class for resources that work with the HTTP protocol.
 *
 * @package Core
 */
class HTTPResource extends Resource {

  use MagicInjection;

  /**
   * Stores a reference to the HTTPUtils class. Injection target.
   * @var \Core\Utils\HTTPUtils
   */
  protected $httpUtils;

  /**
   * Allowed HTTP request methods
   * @var array
   */
  protected $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'];

  /**
   * Stores a reference to the request object.
   * @var \Core\Requests\HTTPRequest
   */
  protected $request = null;

  /**
   * Returns a default response object if the class does not have a specific function for a request method.
   *
   * @return \Core\Responses\Response
   */
  protected function defaultMethodResponse() : \Core\Responses\Response {
    return Response::create('BadRequestResponse');
  }

  /**
   * Returns a response object appropriate for the request. If the class does not have a specific function for handling
   * the request's method, the defaultMethodResponse will be called and it's response returned. To implement a function
   * for a specific request method, define a protected function with it's name starting with the request method name in
   * uppercase (ex. POST) followed by 'MethodResponse':
   * <code>
   * protected function POSTMethodResponse() {
   *   //$this->request can be used here...
   *   //return Response::create('class_name');
   *   //or
   *   //$response = something or new something
   *   //return $response
   * }
   *
   * protected function GETMethodResponse() {
   *   //code here
   * }</code>
   *
   * @param \Core\Requests\HTTPRequest $request A request object
   *
   * @return \Core\Responses\Response A response object
   * @throws \Core\Exceptions\ResourceException
   */
  public function getResponse(\Core\Requests\Request $request) : \Core\Responses\Response {
    if (!($request instanceof \Core\Requests\HTTPRequest)) {
      throw CoreException::create(
        'ResourceException',
        'HTTPResource class only works with HTTPRequest classes, passed "' . get_class($request) . '"'
      );
    }

    $this->request = $request;

    $upperMethod = strtoupper($request->getMethod());
    if (!in_array($upperMethod, $this->allowedMethods)) {
      $response = Response::create('BadRequestResponse');
      $response->setText("Method '$upperMethod' is not allowed for this resource");
      return $response;
    }

    $methodName = $upperMethod . 'MethodResponse';
    if (is_callable([$this, $methodName])) {
      return $this->$methodName();
    } else {
      return $this->defaultMethodResponse();
    }
  }

}
