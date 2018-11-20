<?php

/**
 * CLIResource class
 *
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
 * Base class for resources that work with the CLI.
 *
 * @package Core
 */
class CLIResource extends Resource {

  use MagicInjection;

  /**
   * Stores a reference to the request object.
   * @var \Core\Requests\CLIRequest
   */
  protected $request = null;

  /**
   * Returns an appropriate response for a request.
   *
   * @param \Core\Requests\Request $request A request object.
   *
   * @return \Core\Responses\Response A response object
   */
  public function getResponse(\Core\Requests\Request $request) : \Core\Responses\Response {
    if (!($request instanceof \Core\Requests\CLIRequest)) {
      throw CoreException::create(
        'ResourceException',
        'CLIResource class only works with CLIRequest classes, passed "' . get_class($request) . '"'
      );
    }

    $this->request = $request;

    return $this->processRequest();
  }

  /**
   * Descendant classes should use this method to process the request and return a response, or use the default
   * parent's response.
   *
   * @return \Core\Responses\CLIResponse
   */
  public function processRequest() : \Core\Responses\CLIResponse {
    return Response::create('CLIResponse');
  }

}
