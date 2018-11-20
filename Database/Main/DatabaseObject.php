<?php

/**
 * DatabaseObject class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Database\Main;

use Database\Main\DatabaseClass;

/**
 * This class represents a row of a resultset. Properties are created for every filed in the row, but descendant classes
 * can override this behavior. Useful only through a descendant class.
 *
 * @package Database
 */
abstract class DatabaseObject extends DatabaseClass {

  /**
   * Indicates that the object is not initialized yet. Properties haven't been setup.
   */
  const STATE_UNINITIALIZED = -1;

  /**
   * Indicates that the object is in sync with the database.
   */
  const STATE_SYNCED = 0;

  /**
   * Indicates that the object is new and not exists in the database.
   */
  const STATE_NEW = 1;

  /**
   * Indicates that the object properties does not reflect the ones in the database anymore.
   */
  const STATE_MODIFIED = 2;

  /**
   * Indicates that the object has been deleted from the database.
   */
  const STATE_DELETED = 3;

  /**
   * Stores the value of the object internal state.
   * @var int
   */
  private $state = self::STATE_UNINITIALIZED;

  /**
   * An array of name => value pairs used for magic properties (__set and __get).
   * @var array
   */
  private $properties = []; //protected

  /**
   * An array that stores the names of properties whose values were modified.
   *
   * @var array
   */
  private $modifiedProperties = [];

  /**
   * Constructor.
   *
   * @param \Database\Main\DatabaseConnection $connection A reference to the connection.
   */
  public function __construct(\Database\Main\DatabaseConnection $connection) {
    parent::__construct($connection);
    $this->state = self::STATE_NEW;
  }

  /**
   * Validates a property assignment. A callback that can accept or reject the property assignment and modify the passed
   * value.
   *
   * @param string $name  Name of the property.
   * @param mixed  $value Value of the property (by reference).
   *
   * @return bool True to accept the assignment, False otherwise.
   */
  protected function onPropertySet(string $name, &$value) : bool {
    return true;
  }

  /**
   * A callback that is called whenever a property value is got. It's intended to be used for conversions, for example,
   * return a string representation of a date object or vice-versa.
   *
   * @param string $name  Name of the property.
   * @param mixed  $value The current property value.
   *
   * @return mixed The value returned to the caller.
   */
  protected function onPropertyGet(string $name, $value) {
    return $value;
  }

  /**
   * Returns a DML statament for this object. The statement returned varys according to the request type parameter and
   * will be used to select, delete, update or insert data relevant to this object. Parameters can be used in the
   * statement, for a named parameter use a ":" followed by the parameter's name (ex SELECT * FROM table ORDER BY :field),
   * for a positional parameter just use a "?". Must be implemented by a descendant class.
   *
   * @param int $type One the DatabaseConnection::STATEMENT_* constants.
   *
   * @return string|null An SQL statement or null if there are no statement for that type.
   * @see http://php.net/manual/en/pdo.prepare.php
   */
  abstract public static function getSQLStatement(int $type);

  /**
   * Returns the values for statement parameters. The type parameter dictates for what kind of statement the parameters
   * are. Must be implemented by a descendant class.
   *
   * @param int $type One the DatabaseConnection::STATEMENT_* constants.
   *
   * @return array An array in a name => value format for named parameters or a plain array of just values for
   * positional parameters.
   * @see http://php.net/manual/en/pdostatement.execute.php
   */
  abstract public function getStatementParams(int $type) : array;

  /**
   * Sets the object internal state. This method should not be called directly.
   *
   * @param int $state One of STATE_* constants.
   *
   * @return void
   */
  final public function setState(int $state) {
    if ($state >= -1 && $state <= 3) {
      $this->state = $state;
      if (in_array($this->state, [self::STATE_NEW, self::STATE_SYNCED])) {
        $this->modifiedProperties = [];
      }
    }
  }

  /**
   * Returns the current internal state of the object.
   *
   * @return int One of the STATE_* constants.
   */
  final public function getState() : int {
    return $this->state;
  }

  /**
   * Determines if a property was modified.
   *
   * @param string $name Property's name.
   *
   * @return bool True if the property was modified, False otherwise.
   */
  protected function isPropertyModified(string $name) : bool {
    return in_array($name, $this->modifiedProperties);
  }

  /**
   * Returns all the object's properties as an associative array. This function calls onPropertyGet to get values, also
   * objects will be cloned.
   *
   * @param array $exclude      Array of property names to exclude from the result.
   * @param bool  $modifiedOnly Return only properties that have been modified.
   *
   * @return array An array in a key => value format.
   */
  final public function toArray(array $exclude = [], bool $modifiedOnly = false) : array {
    $result = [];
    foreach ($this->properties as $name => $value) {
      if ((!in_array($name, $exclude)) && (!$modifiedOnly || in_array($name, $this->modifiedProperties))) {
        $result[$name] = $this->onPropertyGet($name, $value);
        if (is_object($result[$name])) {
          $result[$name] = clone $result[$name];
        }
      }
    }
    return $result;
  }

  /**
   * Magic property setter.
   *
   * @param string $name  Property's name.
   * @param mixed  $value Property's value.
   *
   * @return void
   */
  final public function __set(string $name, $value) {
    if ($this->onPropertySet($name, $value)) {
      $this->properties[$name] = $value;
      if (!in_array($this->getState(), [self::STATE_NEW, self::STATE_UNINITIALIZED])) {
        $this->setState(self::STATE_MODIFIED);
        if (!in_array($name, $this->modifiedProperties)) {
          $this->modifiedProperties[] = $name;
        }
      }
    }
  }

  /**
   * Magic property getter.
   *
   * @param string $name Property's name.
   *
   * @return mixed Property's value.
   * @throws \Database\Exceptions\DatabaseException
   */
  final public function __get(string $name) {
    if ($this->__isset($name)) {
      return $this->onPropertyGet($name, $this->properties[$name]);
    } else {
      return null;
    }
  }

  /**
   * Checks if a property exists.
   *
   * @param string $name Property's name.
   *
   * @return bool True if the property exists.
   */
  final public function __isset(string $name) : bool {
    return array_key_exists($name, $this->properties);
  }

}
