<?php

/**
 * CryptUtils class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Utils;

use Core\Exceptions\ApplicationException;

/**
 * Class with methods for hashing and encryption.
 *
 * @package Core
 */
class CryptUtils {

  /**
   * MD5 hash method (16 bytes or 32 hexits).
   */
  const HASH_MD5 = 'md5';

  /**
   * SHA1 hash method (20 bytes or 40 hexits)
   */
  const HASH_SHA1 = 'sha1';

  /**
   * SHA256 hash method (32 bytes or 64 hexits)
   */
  const HASH_SHA256 = 'sha256';

  /**
   * AES-128 cipher method.
   */
  const CIPHER_AES_128 = 'AES-128-CBC';

  /**
   * AES-192 cipher method.
   */
  const CIPHER_AES_192 = 'AES-192-CBC';

  /**
   * AES-256 cipher method.
   */
  const CIPHER_AES_256 = 'AES-256-CBC';

  /**
   * Exception code for exceptions thrown due to problems related to OpenSSL.
   */
  const EXC_CODE_OPENSSL = 1;

  /**
   * Exception code for exceptions thrown due to failed validations.
   */
  const EXC_CODE_VALIDATION = 2;

  /**
   * Exception code for exceptions thrown due to problems related to corrupted data or data in unexpected formats.
   */
  const EXC_CODE_BAD_DATA = 3;

  /**
   * Exception code for unspecific exceptions.
   */
  const EXC_CODE_OTHER = 4;

  /**
   * Stores the exception mode flag.
   * @var boolean
   */
  protected $exceptionMode = false;

  /**
   * Stores the default hash method.
   * @var string
   */
  protected $defaultHashMethod = self::HASH_SHA256;

  /**
   * Stores the default cipher method.
   * @var string
   */
  protected $defaultCipherMethod = self::CIPHER_AES_256;

  /**
   * Returns the user provided hash method or the default method if null/empty is passed.
   *
   * @param string|null $userMethod The user hash method.
   *
   * @return string A hash method.
   */
  protected function selectHashMethod($userMethod) : string {
    return empty($userMethod) ? $this->defaultHashMethod : $userMethod;
  }

  /**
   * Returns the user provided cipher method or the default method if null/empty is passed.
   *
   * @param string|null $userMethod The user cipher method.
   *
   * @return string A cipher method.
   */
  protected function selectCipherMethod($userMethod) : string {
    return empty($userMethod) ? $this->defaultCipherMethod : $userMethod;
  }

  /**
   * Enables or disables the throwing of exceptions from the class methods.
   *
   * @param bool $enabled True to enable exceptions, False to just return False instead.
   *
   * @return void
   */
  public function setExceptionMode(bool $enabled) {
    $this->exceptionMode = $enabled;
  }

  /**
   * Gets the current exception mode value.
   *
   * @return bool True if enabled, False otherwise.
   */
  public function getExceptionMode() : bool {
    return $this->exceptionMode;
  }

  /**
   * Sets the default hash method.
   *
   * @param string $method One of the HASH_* constants.
   *
   * @return void
   */
  public function setDefaultHashMethod(string $method) {
    $this->defaultHashMethod = $method;
  }

  /**
   * Gets the default hash default.
   *
   * @return string
   */
  public function getDefaultHashMethod() : string {
    return $this->defaultHashMethod;
  }

  /**
   * Sets the default cipher method.
   *
   * @param string $method One of the CIPHER_* constants.
   *
   * @return void
   */
  public function setDefaultCipherMethod(string $method) {
    $this->defaultCipherMethod = $method;
  }

  /**
   * Gets the default cipher method.
   *
   * @return string
   */
  public function getDefaultCipherMethod() : string {
    return $this->defaultCipherMethod;
  }

  /**
   * Generates a hash.
   *
   * @param string $data       The data to hashed.
   * @param bool   $binary     True for a binary result, False for lowercase hexits.
   * @param string $hashMethod The method used for hashing. One of the HASH_* constants.
   *
   * @return string A hash string or a binary representarion of the hash.
   */
  public function hash(string $data, bool $binary = false, string $hashMethod = null) : string {
    return hash($this->selectHashMethod($hashMethod), $data, $binary);
  }

  /**
   * Timing attack safe hash comparison.
   *
   * @param string $knowHash The known hash string.
   * @param string $userHash The user supplied hash string. The user supplied hash must be the second parameter.
   *
   * @return bool True if the hashes are equal, False otherwise.
   */
  public function hashEquals(string $knowHash, string $userHash) : bool {
    return hash_equals($knowHash, $userHash);
  }

  /**
   * Generates a keyed hash.
   *
   * @param string $data       The data to be hashed.
   * @param string $key        A key.
   * @param bool   $binary     True for a binary result, False for lowercase hexits.
   * @param string $hashMethod The method used for hashing. One of the HASH_* constants.
   *
   * @return string|boolean Returns the hash string or False on failure.
   */
  public function generateHMAC(string $data, string $key, bool $binary = false, string $hashMethod = null) {
    return hash_hmac($this->selectHashMethod($hashMethod), $data, $key, $binary);
  }

  /**
   * Generates and compares an HMAC against an user supplied hash. Timing attack safe.
   *
   * @param string $data       The data to be hashed.
   * @param string $key        A key.
   * @param string $userHash   The user supplied hash.
   * @param bool   $binary     True if the hash is in a binary format, False otherwise.
   * @param string $hashMethod The method used for hashing. One of the HASH_* constants.
   *
   * @return bool True if validated.
   */
  public function validateHMAC(
    string $data, string $key, string $userHash, bool $binary = false, string $hashMethod = null
  ) : bool {
    return $this->hashEquals($this->generateHMAC($data, $key, $binary, $hashMethod), $userHash);
  }

  /**
   * Generates a hash in lowercase hexits of a secret/password. This method is intended to generate keys for other
   * cryptography methods, although a random key is often preferable.
   *
   * @param string $secret     A secret/password.
   * @param string $hashMethod The method used for hashing. One of the HASH_* constants.
   *
   * @return string A hash string.
   */
  public function keyFromSecret(string $secret, string $hashMethod = null) : string {
    return $this->hash($secret, false, $this->selectHashMethod($hashMethod));
  }

  /**
   * Generates a cryptographically secure pseudo random key with 64 characters.
   *
   * @return string|boolean The key string or False on failure.
   * @throws \Core\Exceptions\ApplicationException
   */
  public function randomKey() {
    try {
      return $this->hash(random_bytes(256), false, self::HASH_SHA256);
    } catch (\Exception $ex) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to generate random key', self::EXC_CODE_OTHER, $ex
        );
      }
      return false;
    }
  }

  /**
   * Encrypts data.
   *
   * @param string $data         The data to be encrypted.
   * @param string $key          A key for encryption.
   * @param string $cipherMethod The cipher method. One of the CIPHER_* constants.
   *
   * @return string|boolean The encrypted data (in base64 format) or False on failure.
   * @throws \Core\Exceptions\ApplicationException
   */
  public function encrypt(string $data, string $key, string $cipherMethod = null) {
    $cipherMethod = $this->selectCipherMethod($cipherMethod);

    if (false === $ivlen = openssl_cipher_iv_length($cipherMethod)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to get IV length for cipher "' . $cipherMethod . '"', self::EXC_CODE_OPENSSL
        );
      }
      return false;
    }
    if (false === $iv = openssl_random_pseudo_bytes($ivlen)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to generate random bytes', self::EXC_CODE_OPENSSL
        );
      }
      return false;
    }
    if (false === $encryptedData = openssl_encrypt($data, $cipherMethod, $key, OPENSSL_RAW_DATA, $iv)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to encrypt, (using "'. $cipherMethod .'")', self::EXC_CODE_OPENSSL
        );
      }
      return false;
    }
    $encryptedData = $iv . $encryptedData;
    return base64_encode($encryptedData);
  }

  /**
   * Decrypts data.
   *
   * @param string $data         The data to be decrypted.
   * @param string $key          A key for decryption.
   * @param string $cipherMethod The cipher method. One of the CIPHER_* constants.
   *
   * @return string|boolean Returns the unencrypted data or False on failure.
   * @throws \Core\Exceptions\ApplicationException
   */
  public function decrypt(string $data, string $key, string $cipherMethod = null) {
    $cipherMethod = $this->selectCipherMethod($cipherMethod);

    if (false === $ivlen = openssl_cipher_iv_length($cipherMethod)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to get IV length for cipher "' . $cipherMethod . '"', self::EXC_CODE_OPENSSL
        );
      }
      return false;
    }
    if (false === $data = base64_decode($data, true)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to decode base64 data', self::EXC_CODE_BAD_DATA
        );
      }
      return false;
    }
    $iv = substr($data, 0, $ivlen);
    if (empty($iv)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to get IV from encrypted data', self::EXC_CODE_BAD_DATA
        );
      }
      return false;
    }
    $data = substr($data, $ivlen);
    if (empty($data)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Invalid encrypted data', self::EXC_CODE_BAD_DATA
        );
      }
      return false;
    }
    if (false === $decryptedData = openssl_decrypt($data, $cipherMethod, $key, OPENSSL_RAW_DATA, $iv)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to decrypt, (using "'. $cipherMethod .'")', self::EXC_CODE_OPENSSL
        );
      }
      return false;
    }
    return $decryptedData;
  }

  /**
   * Encrypts data with a validation HMAC.
   *
   * @param string $data         The data to be encrypted.
   * @param string $key          A key for encryption.
   * @param string $cipherMethod The cipher method. One of the CIPHER_* constants.
   * @param string $hashMethod   The method used for hashing. One of the HASH_* constants.
   *
   * @return string|boolean The encrypted data (in base64 format) or False on failure.
   * @throws \Core\Exceptions\ApplicationException
   */
  public function authenticatedEncrypt(string $data, string $key, string $cipherMethod = null, string $hashMethod = null) {
    $cipherMethod = $this->selectCipherMethod($cipherMethod);
    $hashMethod = $this->selectHashMethod($hashMethod);

    if (false === $ivlen = openssl_cipher_iv_length($cipherMethod)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to get IV length for cipher "' . $cipherMethod . '"', self::EXC_CODE_OPENSSL
        );
      }
      return false;
    }
    if (false === $iv = openssl_random_pseudo_bytes($ivlen)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to generate random bytes', self::EXC_CODE_OPENSSL
        );
      }
      return false;
    }
    if (false === $encryptedData = openssl_encrypt($data, $cipherMethod, $key, OPENSSL_RAW_DATA, $iv)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to encrypt, (using "'. $cipherMethod .'")', self::EXC_CODE_OPENSSL
        );
      }
      return false;
    }
    $encryptedData = $iv . $encryptedData;
    if (false === $hmac = $this->generateHMAC($encryptedData, $key, true, $hashMethod)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to generate HMAC, (using "'. $hashMethod .'")', self::EXC_CODE_OTHER
        );
      }
      return false;
    }
    $encryptedData = $hmac . $encryptedData;
    return base64_encode($encryptedData);
  }

  /**
   * Decrypts data and validates it's HMAC with a timing attack safe method.
   *
   * @param string $data         The data to be decrypted.
   * @param string $key          A key for decryption.
   * @param string $cipherMethod The cipher method. One of the CIPHER_* constants.
   * @param string $hashMethod   The method used for hashing. One of the HASH_* constants.
   *
   * @return string|boolean The unencrypted data or False on failure.
   * @throws \Core\Exceptions\ApplicationException
   */
  public function authenticatedDecrypt(string $data, string $key, string $cipherMethod = null, string $hashMethod = null) {
    $cipherMethod = $this->selectCipherMethod($cipherMethod);
    $hashMethod = $this->selectHashMethod($hashMethod);

    $hashLen = strlen($this->hash($hashMethod, true, $hashMethod));
    if ($hashLen <= 0) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to get hash length for method "' . $hashMethod . '"', self::EXC_CODE_OPENSSL
        );
      }
      return false;
    }
    if (false === $ivlen = openssl_cipher_iv_length($cipherMethod)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to get IV length for cipher "' . $cipherMethod . '"', self::EXC_CODE_OPENSSL
        );
      }
      return false;
    }
    if (false === $data = base64_decode($data, true)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to decode base64 data', self::EXC_CODE_BAD_DATA
        );
      }
      return false;
    }

    $hmac = substr($data, 0, $hashLen);
    if (empty($hmac)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to get HMAC from encrypted data', self::EXC_CODE_BAD_DATA
        );
      }
      return false;
    }

    $iv = substr($data, $hashLen, $ivlen);
    if (empty($iv)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to get IV from encrypted data', self::EXC_CODE_BAD_DATA
        );
      }
      return false;
    }

    $data = substr($data, $hashLen + $ivlen);
    if (empty($data)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Invalid encrypted data', self::EXC_CODE_BAD_DATA
        );
      }
      return false;
    }

    if (!$this->validateHMAC($iv . $data, $key, $hmac, true, $hashMethod)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Encrypted data failed HMAC validation', self::EXC_CODE_VALIDATION
        );
      }
      return false;
    }

    if (false === $decryptedData = openssl_decrypt($data, $cipherMethod, $key, OPENSSL_RAW_DATA, $iv)) {
      if ($this->exceptionMode) {
        throw ApplicationException::createSelf(
          'CryptUtils: Failed to decrypt, (using "'. $cipherMethod .'")', self::EXC_CODE_OPENSSL
        );
      }
      return false;
    }
    return $decryptedData;
  }

}
