<?php

/**
 * ResponseStorage class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Application;

/**
 * Abstract base class for response content storage.
 *
 * @package Core
 */
abstract class ResponseStorage {

  /**
   * Enabled flag.
   * @var bool
   */
  private $enabled = true;

  /**
   * Enables or disables the storage.
   *
   * @param bool $enabled Enables or disables the storage
   *
   * @return void
   */
  final public function setEnabled(bool $enabled) {
    $this->enabled = $enabled;
  }

  /**
   * Determines if the storage is enabled.
   *
   * @return bool True if enabled, False otherwise.
   */
  final public function isEnabled() : bool {
    return $this->enabled;
  }

  /**
   * Stores a response's content. Must be implemented.
   *
   * @param string $uri     The request URI. It is an unique identifier for the content. Perhaps you may want to store
   *                        it as a hash.
   * @param string $content The response content.
   * @param array  $extra   An optional array with extra data to be stored.
   *
   * @return bool True on success, False otherwise.
   */
  abstract protected function internalStore(string $uri, string $content, array $extra = null) : bool;

  /**
   * Retrieves a previously stored response's content. Must be implemented.
   *
   * @param string $uri     The request URI. It is an unique identifier for the content.
   * @param string $content Reference to a variable to which the content will be assigned.
   * @param array  $extra   Reference to a variable to which extra data will be assigned. Optional.
   *
   * @return bool True on success, False otherwise.
   */
  abstract protected function internalRetrieve(string $uri, string &$content, array &$extra = null) : bool;

  /**
   * Removes (deletes) a previously stored response's content. Must be implemented.
   *
   * @param string $uri The request URI. It is an unique identifier for the content.
   *
   * @return bool True on success, False otherwise.
   */
  abstract protected function internalRemove(string $uri) : bool;

  /**
   * Stores a response's content.
   *
   * @param string $uri     The request URI. It is an unique identifier for the content. Perhaps you may want to store
   *                        it as a hash.
   * @param string $content The response content.
   * @param array  $extra   An optional array with extra data to be stored.
   *
   * @return bool True on success, False on failure or if disabled.
   */
  final public function store(string $uri, string $content, array $extra = null) : bool {
    if (!$this->enabled) {
      return false;
    } else {
      return $this->internalStore($uri, $content, $extra);
    }
  }

  /**
   * Retrieves a previously stored response's content.
   *
   * @param string $uri     The request URI. It is an unique identifier for the content.
   * @param string $content Reference to a variable to which the content will be assigned.
   * @param array  $extra   Reference to a variable to which extra data will be assigned. Optional.
   *
   * @return bool True on success, False on failure or if disabled.
   */
  final public function retrieve(string $uri, string &$content, array &$extra = null) : bool {
    if (!$this->enabled) {
      return false;
    } else {
      return $this->internalRetrieve($uri, $content, $extra);
    }
  }

  /**
   * Removes (deletes) a previously stored response's content.
   *
   * @param string $uri The request URI. It is an unique identifier for the content.
   *
   * @return bool True on success, False on failure or if disabled.
   */
  final public function remove(string $uri) : bool {
    if (!$this->enabled) {
      return false;
    } else {
      return $this->internalRemove($uri);
    }
  }

}
