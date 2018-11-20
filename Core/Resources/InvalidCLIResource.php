<?php

/**
 * InvalidCLIResource class
 *
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Resources;

use Core\Resources\CLIResource;

/**
 * Resource class for invalid (not found) CLI resources.
 *
 * @package Core
 */
class InvalidCLIResource extends CLIResource {

  /**
   * Processes the request and returns a response with a default message.
   *
   * @return \Core\Responses\CLIResponse
   */
  public function processRequest() : \Core\Responses\CLIResponse {
    $response = parent::processRequest();
    $response->setText('ERROR: "' . $this->request->getURI() . '" is not a valid resource command.');
    return $response;
  }

}
