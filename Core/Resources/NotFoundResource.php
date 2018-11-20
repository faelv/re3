<?php

/**
 * NotFoundResource class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Resources;

use Core\Resources\CacheableResource;
use Core\Responses\Response;

/**
 * Resource class that noly returns NotFound Responses.
 *
 * @package Core
 */
class NotFoundResource extends CacheableResource {

  /**
   * Returns a default response object if the class does not have a specific function for a request method.
   *
   * @return \Core\Responses\Response
   */
  protected function defaultMethodResponse() : \Core\Responses\Response {
    $response = Response::create('NotFoundResponse');
    $response->setText('"' . $this->request->getURI() . '" was not found on this server');
    return $response;
  }

}
