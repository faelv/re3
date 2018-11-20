<?php

/**
 * StaticResource class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Resources;

use Core\FileSystem\File;
use Core\Resources\CacheableResource;
use Core\Responses\Response;
use Core\Exceptions\CoreException;

/**
 * Resource class intended for sending contents of a file.
 *
 * @package Core
 */
class StaticResource extends CacheableResource {

  /**
   * Inherit this method and return the filename that will be used by this resource.
   *
   * @return string The filename.
   */
  protected function getFilename() : string {
    return '';
  }

  /**
   * Returns one the following responses:
   * - StaticResponse, for sending the file contents.
   * - NotModifiedResponse, if it was determined that the browser cache is fresh.
   * - NotFoundResponse, if the file was not found.
   * - ServerErrorResponse, in case of a unexpected error.
   *
   * @return \Core\Responses\Response
   * @throws \Core\Exceptions\FileSystemException
   */
  protected function GETMethodResponse() : \Core\Responses\Response {
    $filename = $this->getFilename();
    if (File::exists($filename)) {
      try {
        $file = new File($filename);
        $fileModifiedDate = $file->modifiedDate();
        if ($this->cacheIsFresh(null, $fileModifiedDate)) {
          $response = Response::create('NotModifiedResponse');
          $response->setModifiedDate($fileModifiedDate);
        } else {
          if (!$file->open(File::MODE_READ)) {
            throw CoreException::create('FileSystemException', 'Could not read the file');
          }
          $response = Response::create('StaticResponse');
          $response->setFile($file);
        }
      } catch (\Exception $ex) {
        $response = Response::create('ServerErrorResponse');
      }
    } else {
      $response = Response::create('NotFoundResponse');
      $response->setText('"' . $filename . '" was not found on this server');
    }
    return $response;
  }

}
