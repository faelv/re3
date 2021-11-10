<?php

/**
 * DatabaseCollection class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Database\Main;

use Database\Main\DatabaseClass;
use Database\Main\DatabaseObject;
use Database\Exceptions\DatabaseCollectionException;

/**
 * A class that stores a list of database objects. Useful only through a descendant class.
 *
 * @package Database
 */
abstract class DatabaseCollection extends DatabaseClass implements \Iterator, \Countable {

  /**
   * Internal items array.
   * @var array
   */
  protected $items = [];

  /**
   * Internal array for removed (but not yet deleted) items.
   * @var array
   */
  protected $removedItems = [];

  /**
   * Stores the iterator key for the iterator interface.
   * @var int
   */
  protected $iteratorKey = -1;

  /**
   * Returns the class name of the items that this collection works with. Must be implemented by a descendant class.
   * It's better to return a fully qualified class name.
   *
   * @return string Class name
   */
  abstract public static function getItemsClass() : string;

  /**
   * Returns a SELECT statement that is used to select objects for this collection. Parameters can be used in the
   * statement, for a named parameter use a ":" followed by the parameter's name (ex SELECT * FROM table ORDER BY :field),
   * for a positional parameter just use a "?". Must be implemented by a descendant class.
   *
   * @return string An SQL SELECT statement.
   * @see http://php.net/manual/en/pdo.prepare.php
   */
  abstract public static function getItemsSelectStatement() : string;

  /**
   * Returns a DML statament for this object. The statement returned varys according to the request type parameter and
   * will be used to select, delete, update or insert data relevant to this object. Parameters can be used in the
   * statement, for a named parameter use a ":" followed by the parameter's name (ex SELECT * FROM table ORDER BY :field),
   * for a positional parameter just use a "?". Must be implemented by a descendant class.
   *
   * @param int $type One the DatabaseConnection::STATEMENT_* constants. Currently only STATEMENT_SELECT is used with
   *                  collections.
   *
   * @return string|null An SQL statement or null if there are no statement for that type.
   * @see http://php.net/manual/en/pdo.prepare.php
   */
  public static function getSQLStatement(int $type) {
    if ($type == \Database\Main\DatabaseConnection::STATEMENT_SELECT) {
      return static::getItemsSelectStatement();
    }
    return null;
  }

  /**
   * Gets an item by it's index.
   *
   * @param int $index Index of the item.
   *
   * @return \Database\Main\DatabaseObject
   * @throws \Database\Exceptions\DatabaseCollectionException
   */
  public function item(int $index) : \Database\Main\DatabaseObject {
    if ($index < 0 || $index >= $this->count()) {
      throw DatabaseCollectionException::createSelf('Index "' . $index . '" is out of bounds');
    }
    return $this->items[$index];
  }

  /**
   * Gets the index of an item.
   *
   * @param \Database\Main\DatabaseObject $item A DatabaseObject
   *
   * @return boolean|int The index of the item or False if the item isn't in the collection. Be careful to not evaluate
   * 0 (zero) as False.
   */
  public function index(\Database\Main\DatabaseObject $item) {
    return array_search($item, $this->items, true);
  }

  /**
   * Finds an item that has an specific property with an specific value. If more than one item matches the combination
   * the first one will be returned.
   *
   * @param string $property Property's name.
   * @param mixed  $value    Property's value.
   *
   * @return null|\Database\Main\DatabaseObject An item or Null if no items match.
   */
  public function find(string $property, $value) {
    foreach ($this->items as $item) {
      if ($item->__isset($property) || property_exists($item, $property)) {
        if ($item->$property === $value) {
          return $item;
        }
      }
    }
    return null;
  }

  /**
   * Finds all items that has an specific property with an specific value.
   *
   * @param string $property Property's name.
   * @param mixed  $value    Property's value.
   *
   * @return array An array containing all the items that matched the property/value combination.
   */
  public function findAll(string $property, $value) : array {
    $result = [];
    foreach ($this->items as $item) {
      if ($item->__isset($property) || property_exists($item, $property)) {
        if ($item->$property === $value) {
          $result[] = $item;
        }
      }
    }
    return $result;
  }

  /**
   * Makes the item inaccessible in the collection, but keeps a reference to it so it can be deleted from the database
   * later. You can make a removed item accessible again by adding it to the collection again.
   *
   * @param int|\Database\Main\DatabaseObject $item Item to remove or it's index.
   *
   * @return void
   * @throws \Database\Exceptions\DatabaseCollectionException
   */
  public function remove($item) {
    if (is_int($item)) {
      $this->remove($this->item($item));
      return;
    }

    $index = $this->index($item);
    if ($index === false) {
      throw DatabaseCollectionException::createSelf('Could not remove the item, it is not in the collection.');
    }
    $this->removedItems[] = $this->items[$index];
    array_splice($this->items, $index, 1);
  }

  /**
   * Removes the item from the collection. No matter if it is accessible or was previously removed, no reference to it
   * will be kept and no further actions will be performed on it.
   *
   * @param int|\Database\Main\DatabaseObject $item Item to dispose or it's index. If an index is used it will not be
   *                                                possible to dispose removed items, only accessible ones.
   *
   * @return void
   * @throws \Database\Exceptions\DatabaseCollectionException
   */
  public function dispose($item) {
    if (is_int($item)) {
      $this->dispose($this->item($item));
      return;
    }

    $index = array_search($item, $this->removedItems, true);
    if ($index !== false) {
      array_splice($this->items, $index, 1);
    } else {
      $index = $this->index($item);
      if ($index === false) {
        throw DatabaseCollectionException::createSelf('Could not dispose the item, it is not in the collection.');
      }
      array_splice($this->items, $index, 1);
    }
  }

  /**
   * Adds an item to the collection. If the item is already in the collection it will not be added again. If the
   * item was previously removed (not disposed) it will become accessible again under a new index number.
   *
   * @param \Database\Main\DatabaseObject $item The item to add.
   *
   * @return int The index of the item.
   * @throws \Database\Exceptions\DatabaseCollectionException
   */
  public function add(\Database\Main\DatabaseObject $item) {
    if (!($item instanceof DatabaseObject)) {
      throw DatabaseCollectionException::createSelf('Could not add the item, it is not a DatabaseObject.');
    }
    if (!is_a($item, static::getItemsClass())) {
      throw DatabaseCollectionException::createSelf('Only ' . static::getItemsClass() . ' items are allowed in this collection');
    }
    $removed = array_search($item, $this->removedItems, true);
    if ($removed !== false) {
      array_splice($this->removedItems, $removed, 1);
    }
    $index = array_search($item, $this->items, true);
    if ($index !== false) {
      return $index;
    } else {
      $this->items[] = $item;
      return count($this->items) - 1;
    }
  }

  /**
   * Creates a new item and add it to the collection.
   *
   * @param mixed ...$extra Any number of parameters of any kind, passed to the object constructor.
   *
   * @return \Database\Main\DatabaseObject The new item.
   */
  public function addNew(...$extra) : \Database\Main\DatabaseObject {
    $newItem = $this->connection->newObject(static::getItemsClass(), ...$extra);
    $this->add($newItem);
    return $newItem;
  }

  /**
   * Adds items from an array. Items will not be added if their classes are not allowed in the collection.
   *
   * @param array $array An array of database objects.
   *
   * @return int The number of added items.
   */
  public function addArray(array $array) : int {
    $added = 0;
    foreach ($array as $item) {
      try {
        $this->add($item);
        $added++;
      } catch (DatabaseCollectionException $ex) {
        //ignore this exception
      }
    }
    return $added;
  }

  /**
   * Gets the number of removed items.
   *
   * @return int
   */
  public function removedCount() : int {
    return count($this->removedItems);
  }

  /**
   * Returns an array containing references to the removed items.
   *
   * @return array An array of database objects.
   */
  public function getRemovedItems() : array {
    return $this->removedItems;
  }

  /**
   * Converts a collection to an array, optionally converting the items too.
   *
   * @param bool $convertItems True to also convert this collection's items, false to keep them as objects.
   *
   * @return array An array of database objects or an array of arrays if $convertItems is true
   */
  public function toArray(bool $convertItems = true) : array {
    return array_map(
      function ($item) use ($convertItems) {
        return $convertItems ? $item->toArray() : $item;
      },
      iterator_to_array($this, true)
    );
  }

  //Below are interface methods

  /**
   * Returns the number of items in this collection.
   *
   * @return int Number of items.
   */
  final public function count() : int {
    return count($this->items);
  }

  /**
   * Interface method. Returns the current iterator item.
   *
   * @return \Database\Main\DatabaseObject
   */
  final public function current() : \Database\Main\DatabaseObject {
    return ($this->iteratorKey >= 0) ? $this->items[$this->iteratorKey] : null;
  }

  /**
   * Interface method. Returns the current iterator key.
   *
   * @return int
   */
  final public function key() : int {
    return ($this->iteratorKey >= 0) ? $this->iteratorKey : null;
  }

  /**
   * Interface method. Advances the iterator key.
   *
   * @return void
   */
  final public function next() {
    if ($this->iteratorKey >= 0) {
      $this->iteratorKey++;
    }
  }

  /**
   * Interface method. Sets the iterator key back to beginning.
   *
   * @return void
   */
  final public function rewind() {
    $this->iteratorKey = ($this->count() > 0) ? 0 : -1;
  }

  /**
   * Interface method. Tells if the current iterator key is valid.
   *
   * @return bool True if valid.
   */
  final public function valid() : bool {
    return ($this->iteratorKey >= 0 && $this->iteratorKey < $this->count());
  }

}
