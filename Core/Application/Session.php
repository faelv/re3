<?php

/**
 * Session class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Application;

/**
 * Class used for session management.
 *
 * @package Core
 */
class Session {

  /**
   * Inactive session. A session can be restored or opened.
   */
  const STATE_INACTIVE = 0;

  /**
   * Open (new or restored) session. Data can be added to the session.
   */
  const STATE_OPEN = 1;

  /**
   * Closed session. The session data has been saved and will be sent to the browser. New data cannot be added.
   */
  const STATE_CLOSED = 2;

  /**
   * Stores a reference to a CryptUtils class instance. Injection target.
   * @var \Core\Utils\CryptUtils
   */
  public $cryptUtils;

  /**
   * Stores the session internal state.
   * @var int
   */
  protected $state = self::STATE_INACTIVE;

  /**
   * Stores session data and metadata.
   * @var array
   */
  protected $data = ['data' => [], 'meta' => []];

  /**
   * Stores session cookie information.
   * @var array
   */
  protected $cookie = [];

  /**
   * Stores the session data encryption key.
   * @var string
   */
  protected $encryptionKey = '';

  /**
   * Stores the internal data modified flag.
   * @var boolean
   */
  protected $modified = false;

  /**
   * Constructor.
   *
   * @param string $encryptionKey  Key used to encrypt and decrypt the session data.
   * @param string $cookieName     Name of the session cookie. Optional.
   * @param int    $cookieLifetime Lifetime, in seconds, of the session cookie (a relative time interval). Optional,
   *                               if not specified the cookie will last while the browser is open.
   * @param bool   $cookieSecure   True if the cookie should only be sent over secure connections. Optional, defaults
   *                               to False.
   * @param string $cookiePath     The path on the server in which the cookie will be available on. Optional, defaults
   *                               to / (any path).
   * @param string $cookieDomain   The (sub)domain that the cookie is available to. Optional, defaults to the current
   *                               domain only.
   */
  public function __construct(
    $encryptionKey,
    $cookieName = null,
    $cookieLifetime = null,
    $cookieSecure = null,
    $cookiePath = null,
    $cookieDomain = null
  ) {
    $this->cookie['name']    = is_null($cookieName) ? '_SESSION' : $cookieName;
    $this->cookie['life']    = is_null($cookieLifetime) ? 0 : $cookieLifetime;
    $this->cookie['expires'] = is_null($cookieLifetime) ? 0 : time() + $cookieLifetime;
    $this->cookie['path']    = is_null($cookiePath) ? '/' : $cookiePath;
    $this->cookie['domain']  = is_null($cookieDomain) ? '' : $cookieDomain;
    $this->cookie['secure']  = is_null($cookieSecure) ? false : $cookieSecure;

    $this->encryptionKey = $encryptionKey;
  }

  /**
   * Gets the current session state.
   *
   * @return int One of the STATE_* constants.
   */
  public function getState() {
    return $this->state;
  }

  /**
   * Adds data to the session. Data can only be set while the session is open.
   *
   * @param string $key   The data key.
   * @param mixed  $value The data.
   *
   * @return void
   */
  public function setData(string $key, $value) {
    if ($this->state == self::STATE_OPEN) {
      $this->data['data'][$key] = $value;
      $this->modified = true;
    }
  }

  /**
   * Adds data to the session. Data can only be set while the session is open.
   *
   * @param array $data An array in a key => value format.
   *
   * @return void
   */
  public function setDataArray(array $data) {
    foreach ($data as $key => $value) {
      $this->setData($key, $value);
    }
  }

  /**
   * Gets previously set data.
   *
   * @param string $key     The data key.
   * @param mixed  $default The default value to return if the key is not found.
   *
   * @return mixed Returns the current data for the key or the default value if the key is not found.
   */
  public function getData(string $key, $default = null) {
    return $this->data['data'][$key] ?? $default;
  }

  /**
   * Removes data from the session. Data can only be removed while the session is open.
   *
   * @param string $key The data key.
   *
   * @return bool True if data corresponding to the key exists and was removed, False otherwise or if the session isn't
   * open.
   */
  public function unsetData(string $key) : bool {
    if ($this->state == self::STATE_OPEN && isset($this->data['data'][$key])) {
      unset($this->data['data'][$key]);
      return true;
    }
    return false;
  }

  /**
   * Adds metadata to the session. Metadata can only be set while the session is open.
   *
   * @param string $key   The data key.
   * @param mixed  $value The data.
   *
   * @return void
   */
  protected function setMetaData(string $key, $value) {
    if ($this->state == self::STATE_OPEN) {
      $this->data['meta'][$key] = $value;
      $this->modified = true;
    }
  }

  /**
   * Removes metadata from the session. Metadata can only be removed while the session is open.
   *
   * @param string $key The metadata key.
   *
   * @return bool True if metadata corresponding to the key exists and was removed, False otherwise or if the session
   * isn't open.
   */
  protected function unsetMetaData(string $key) : bool {
    if ($this->state == self::STATE_OPEN && isset($this->data['meta'][$key])) {
      unset($this->data['meta'][$key]);
      return true;
    }
    return false;
  }

  /**
   * Gets previously set metadata.
   *
   * @param string $key     The metadata key.
   * @param mixed  $default The default value to return if the key is not found.
   *
   * @return mixed Returns the current metadata for the key or the default value if the key is not found.
   */
  protected function getMetaData(string $key, $default = null) {
    return $this->data['meta'][$key] ?? $default;
  }

  /**
   * Restores a session sent by the browser. Exceptions can be thrown depending on the CryptUtils settings.
   *
   * @param \Core\Requests\HTTPRequest $request        A reference to the request.
   * @param bool                       $extendLifetime If True, the restored session expiration time will be reset.
   *
   * @return bool True on succes, False otherwise.
   */
  public function restore(\Core\Requests\HTTPRequest $request, bool $extendLifetime = true) : bool {
    if ($this->state != self::STATE_OPEN) {
      if (false === $cookieData = $request->getCookieData($this->cookie['name'], false, true)) {
        return false;
      }

      if (false === $unencryptedData = $this->cryptUtils->authenticatedDecrypt($cookieData, $this->encryptionKey)) {
        return false;
      }

      $jsonData = json_decode($unencryptedData, true, 8);
      if (empty($jsonData) || !isset($jsonData['data']) || !isset($jsonData['meta'])) {
        return false;
      }

      $expires = $jsonData['meta']['expires'] ?? 0;

      if (!is_int($expires) || ( $this->cookie['life'] > 0 && $expires <= time() )) {
        return false;
      }

      $this->data['data'] = $jsonData['data'];
      $this->data['meta'] = $jsonData['meta'];

      $this->state = self::STATE_OPEN;
      $this->modified = false;

      if ($extendLifetime && $this->cookie['life'] > 0) {
        $this->setMetaData('expires', time() + $this->cookie['life']);
      }

      return true;
    }
    return false;
  }

  /**
   * Opens a new session.
   *
   * @return bool True on succes, False otherwise.
   */
  public function open() : bool {
    if ($this->state != self::STATE_OPEN) {
      $this->data['data'] = [];
      $this->data['meta'] = ['expires' => $this->cookie['expires'], 'created' => time()];
      $this->state = self::STATE_OPEN;
      $this->modified = false;
      return true;
    }
    return false;
  }

  /**
   * Closes an open session and send the session data to the browser. Exceptions can be thrown depending on the
   * CryptUtils settings.
   *
   * @param \Core\Responses\HTTPResponse $response A reference to the response.
   *
   * @return bool True on succes, False otherwise. Remember that even returning True the browser may not accept the
   * session cookie.
   */
  public function close(\Core\Responses\HTTPResponse $response) : bool {
    if ($this->state == self::STATE_OPEN) {
      if ($this->modified) {
        if (false === $cookieData = json_encode($this->data, JSON_UNESCAPED_UNICODE, 8)) {
          return false;
        }

        if (false === $encryptedData = $this->cryptUtils->authenticatedEncrypt($cookieData, $this->encryptionKey)) {
          return false;
        }

        $response->setCookie(
          $this->cookie['name'],
          $encryptedData,
          $this->cookie['expires'],
          $this->cookie['path'],
          $this->cookie['domain'],
          $this->cookie['secure'],
          true
        );
      }
      $this->state = self::STATE_CLOSED;
      return true;
    }
    return false;
  }

  /**
   * Destroys an open session. This method forces the session cookie expiration.
   *
   * @param \Core\Responses\HTTPResponse $response A reference to the response.
   *
   * @return bool True on succes, False on failure. Remember that even returning True the browser may not accept the
   * session cookie.
   */
  public function destroy(\Core\Responses\HTTPResponse $response) : bool {
    if ($this->state == self::STATE_OPEN) {
      $response->unsetCookie(
        $this->cookie['name'],
        $this->cookie['path'],
        $this->cookie['domain'],
        $this->cookie['secure'],
        true
      );
      $this->state = self::STATE_CLOSED;
      return true;
    }
    return false;
  }

}
