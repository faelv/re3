<?php

/**
 * Settings class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Application;

use Core\FileSystem\File;

/**
 * Class for app settings storage.
 *
 * @package Core
 */
class Settings {

  /**
   * Stores settings names and values.
   * @var array
   */
  protected $data = [];

  /**
   * Store settings default values.
   * @var array
   */
  protected $defaults = [];

  /**
   * The last filename used to load or save settings.
   * @var string
   */
  protected $lastFilename = null;

  /**
   * Constructor.
   *
   * @param string $filename A file to load settings from. Optional.
   * @param array  $defaults An array in a key => value format with settings default values. These values are useful
   *                         when you don't want to specify a default value every time you get a setting.
   */
  public function __construct(string $filename = null, array $defaults = null) {
    if (!empty($filename)) {
      $this->load($filename);
    }
    if (is_array($defaults)) {
      $this->defaults = $defaults;
    }
  }

  /**
   * Sets a setting.
   *
   * @param string $name  Setting name.
   * @param mixed  $value Setting value.
   *
   * @return void
   */
  public function set(string $name, $value) {
    $this->data[$name] = $value;
  }

  /**
   * Gets the value of a setting.
   *
   * @param string $name    Setting name.
   * @param mixed  $default Default value if the setting is not found, if not specified (or null) the default value
   *                        will come from the defaults passed to the constructor, if a corresponding default value was
   *                        not supplied to the constructor either, returns null.
   *
   * @return mixed The setting value or null when the setting is not found and there are no default values.
   */
  protected function getSingleSetting(string $name, $default = null) {
    if (isset($this->data[$name])) {
      return $this->data[$name];
    } else {
      if (!is_null($default)) {
        return $default;
      } else {
        return isset($this->defaults[$name]) ? $this->defaults[$name] : null;
      }
    }
  }

  /**
   * Gets values for an array of setting names.
   *
   * @param array $names An array of setting names. If default values are needed, pass an array for every setting with
   *                     the first element being the setting name and the second being the default value.
   *
   * @return array An array containing the setting values, if a value for a setting is not found or the setting name is
   * not a string, sets the corresponding position in the array to null.
   */
  protected function getMultiSetting(array $names) : array {
    $result = [];
    foreach ($names as $name) {
      if (!is_array($name)) {
        $name = [$name, null];
      } elseif (count($name) == 0) {
        $name[] = null;
      }

      if (!is_string($name[0])) {
        $result[] = null;
      } else {
        $result[] = $this->getSingleSetting($name[0], (count($name) > 1 ? $name[1] : null));
      }
    }
    return $result;
  }

  /**
   * Gets the value of a setting or a list of settings.
   *
   * @param mixed ...$parameters A variable number of parameters can be passed to this method.
   *                             If 1 parameter is passed it must be a string and will be treated as the name of a
   *                             setting. If a value for that setting is not found, returns null (or a constructor
   *                             default value, if any), otherwise returns the setting value.
   *
   *                             If 2 parameters are passed and the first is a string it will be treated as the the name
   *                             of a setting and the second as the default value. If a value for that setting is not
   *                             found, returns the default value, otherwise returns the setting value.
   *
   *                             If 2 parameters are passed and the first is an array, then all other must be arrays too
   *                             (plain values will be treated as single element arrays). In this case the first element
   *                             of every array will be treated as the name of a setting and the second, if present, as
   *                             the default value. Returns an array containing the setting values, if a value for a
   *                             setting is not found or the setting name is not a string, the corresponding position in
   *                             the array will be set to null.
   *
   *                             If 3 or more are passed they all will be treated as arrays with plain values being
   *                             treated as single element arrays. Example:
   *                             <code>
   *                             get('setting');
   *                             get('setting', default);
   *                             get('setting_a', 'setting_b', 'setting_c');
   *                             get(['setting_a', default_a], ['setting_b', default_b]);
   *                             get(['setting_a', default_a], ['setting_b', default_b], ['setting_c']);
   *                             </code>
   *
   *                             Throws an exception if called without any parameter or with a invalid parameter combination.
   *
   * @return mixed|array
   * @throws \LogicException
   */
  public function get(...$parameters) {
    $count = count($parameters);
    if ($count == 0) {

      throw new \LogicException('No parameters have been supplied to the method.');

    } elseif ($count == 1) {

      if (!is_string($parameters[0])) {
        throw new \LogicException('If only one parameter was passed, it must be a string.');
      }
      return $this->getSingleSetting($parameters[0]);

    } elseif ($count == 2) {

      if (is_string($parameters[0])) {
        return $this->getSingleSetting($parameters[0], $parameters[1]);
      } elseif (is_array($parameters[0])) {
        return $this->getMultiSetting($parameters);
      } else {
        throw new \LogicException('The first parameter was expected to be a string or an array, not "' . gettype($parameters[0]) . '"');
      }

    } else {

      return $this->getMultiSetting($parameters);

    }
  }

  /**
   * Saves settings to a file. JSON encoding will be used.
   *
   * @param string $filename If not present or null will use the last filename used to load or save.
   *
   * @return bool True on success.
   */
  public function save(string $filename = null) : bool {
    if (!is_null($filename)) {
      $this->lastFilename = $filename;
    }
    if (is_null($this->lastFilename)) {
      return false;
    }
    $file = new File($this->lastFilename);
    if ($file->open(File::MODE_READ_WRITE_ERASE)) {
      return $file->write(json_encode($this->data, JSON_UNESCAPED_UNICODE, 8)) !== false;
    }
    return false;
  }

  /**
   * Loads settings from a file. Must be a JSON encoded file.
   *
   * @param string $filename A filename.
   *
   * @return bool True on success.
   */
  public function load(string $filename) : bool {
    $result = false;
    $this->lastFilename = $filename;
    $file = new File($this->lastFilename);
    if ($file->open(File::MODE_READ)) {
      if (false !== $jsonStr = $file->readAll()) {
        $this->data = json_decode($jsonStr, true, 8);
        $result = true;
      }
      $file->close();
    }
    return $result;
  }

}
