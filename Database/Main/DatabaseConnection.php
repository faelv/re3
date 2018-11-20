<?php

/**
 * DatabaseConnection class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Database\Main;

use Database\Main\DatabaseClassFactory;
use Database\Main\DatabaseStatement;
use Database\Main\DatabaseObject;
use Database\Main\DatabaseCollection;
use Database\Exceptions\DatabaseLogicException;
use Database\Exceptions\DatabaseException;
use Database\Exceptions\DatabaseConnectionException;

/**
 * This class is used for establishing connections to databases and use them through actions performed by specialized
 * objects and object collections. Methods for query execution and transactions are also available.
 *
 * @package Database
 */
class DatabaseConnection {

  /**
   * An SQL select statement.
   */
  const STATEMENT_SELECT = 1;

  /**
   * An SQL delete statement.
   */
  const STATEMENT_DELETE = 2;

  /**
   * An SQL update statement.
   */
  const STATEMENT_UPDATE = 3;

  /**
   * An SQL insert statement.
   */
  const STATEMENT_INSERT = 4;

  /**
   * Class factory for batabase objects and collections.
   * @var \Database\Main\DatabaseClassFactory
   */
  protected $classFactory = null;

  /**
   * Internal PDO object.
   * @var \PDO
   */
  protected $pdo = null;

  /**
   * Cache for reusable statements.
   * @var array
   */
  protected $statementCache = [];

  /**
   * Flag for automatic collections automatic transactions.
   * @var boolean
   */
  protected $collectionTransactions = true;

  /**
   * Flag for promiscuous mode.
   * @var boolean
   */
  protected $promiscuousMode = false;

  /**
   * Returns a PDO instance.
   *
   * @param string $dsn      The Data Source Name, string with connection information.
   * @param string $username DSN username.
   * @param string $password DSN password.
   * @param array  $options  An array in a key => value format with specific database settings.
   *
   * @return \PDO A PDO object
   */
  protected function getPDO(string $dsn, string $username, string $password, array $options) : \PDO {
    return new \PDO($dsn, $username, $password, $options);
  }

  /**
   * Returns the prepared statement class used by the internal PDO object.
   *
   * @return string The statement class name.
   */
  protected function getStatementClass() : string {
    return DatabaseStatement::class;
  }

  /**
   * Constructor.
   *
   * @param string $dsn      The Data Source Name, string with connection information.
   * @param string $username DSN username.
   * @param string $password DSN password.
   * @param array  $options  An array in a key => value format with specific database settings or an array where each
   *                         element is an array with the option key in the "key" element and the option value in the
   *                         "value" element:
   *                         <code>['buffered_query' => ['key'=>1000, 'value'=>false]]</code>
   *
   * @throws \Database\Exceptions\DatabaseConnectionException
   */
  final public function __construct(string $dsn, string $username = '', string $password = '', array $options = null) {
    if (is_array($options)) {
      $plainOptions = [];
      foreach ($options as $opt_key => $opt_value) {
        if (!is_array($opt_value)) {
          $plainOptions[$opt_key] = $opt_value;
        } elseif (isset($opt_value['key']) && isset($opt_value['value'])) {
          $plainOptions[$opt_value['key']] = $opt_value['value'];
        }
      }
    }
    try {
      $this->pdo = $this->getPDO($dsn, $username, $password, $plainOptions);
    } catch (\Exception $ex) {
      throw DatabaseConnectionException::createSelf($ex->getMessage(), $ex->getCode(), $ex);
    }
    if (!($this->pdo instanceof \PDO)) {
      throw DatabaseConnectionException::createSelf('Could not create the internal PDO object or it is invalid.');
    }
    $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    $this->pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [$this->getStatementClass(), [$this]]);
    $this->classFactory = new DatabaseClassFactory();
  }

  /**
   * Enables or disables the automatic transactions for operations with collections. If enabled all operations that
   * loops through a collection's items will happen inside a transaction, at the end the transaction will always be
   * commited. If it is not desirable, disable these automatic transactions. Not using transactions can lower the
   * application performance and cause inconsistencies due to the chance of selection of incomplete data. Although not
   * all databases support transactions.
   *
   * @param bool $enabled True to enable, False otherwise.
   *
   * @return void
   */
  public function setCollectionTransactions(bool $enabled) {
    $this->collectionTransactions = $enabled;
  }

  /**
   * Checks if automatic transactions are enabled for operation with collections.
   *
   * @return bool True if enabled, False otherwise.
   */
  public function collectionTransactionsEnabled() : bool {
    return $this->collectionTransactions;
  }

  /**
   * Enables or disables promiscuous mode. In this mode, methods that work with objects won't verify the object internal
   * state before accepting it. For example, you will get an exception if you pass a newly created object for a delete
   * method because since the object is marked as new, in theory it doesn't exists in the database yet. By enabling
   * promiscuous mode a delete statement will always be executed, irregardless of the object state.
   *
   * @param bool $enabled True to enable, False to disable.
   *
   * @return void
   */
  public function setPromiscuousMode(bool $enabled) {
    $this->promiscuousMode = $enabled;
  }

  /**
   * Determines if promiscuous mode is enabled.
   *
   * @return bool True if it is enabled, False otherwise.
   */
  public function isPromiscuousModeEnabled() : bool {
    return $this->promiscuousMode;
  }

  /**
   * Executes an SQL statement.
   *
   * @param string $statement An SQL statement.
   *
   * @return int|boolean The number of affected rows or False on failure.
   * @throws \Database\Exceptions\DatabaseConnectionException
   */
  public function execute(string $statement) {
    try {
      return $this->pdo->exec($statement);
    } catch (\Exception $ex) {
      throw DatabaseConnectionException::createSelf($ex->getMessage(), $ex->getCode(), $ex);
    }
  }

  /**
   * Prepares an SQL statement.
   *
   * @param string $statement The statement.
   *
   * @return \Database\Main\DatabaseStatement The prepared statement object.
   * @throws \Database\Exceptions\DatabaseConnectionException
   * @see http://php.net/manual/en/pdo.prepare.php
   */
  public function prepare(string $statement) : \Database\Main\DatabaseStatement {
    try {
      $result = $this->pdo->prepare($statement);
    } catch (\Exception $ex) {
      throw DatabaseConnectionException::createSelf($ex->getMessage(), $ex->getCode(), $ex);
    }
    if ($result === false) {
      throw DatabaseConnectionException::createSelf('Could not create the prepared statement');
    }
    return $result;
  }

  /**
   * Returns the ID of the last inserted row, or the last value from a sequence object, depending on the database in use.
   * Some databases returns 0 (zero) if this method is called after a transaction commit.
   *
   * @param string $name Name of the sequence object if the database needs it.
   *
   * @return string The last ID or sequence value.
   * @throws \Database\Exceptions\DatabaseConnectionException
   */
  public function getLastInsertId(string $name = null) : string {
    try {
      return $this->pdo->lastInsertId($name);
    } catch (\Exception $ex) {
      throw DatabaseConnectionException::createSelf($ex->getMessage(), $ex->getCode(), $ex);
    }
  }

  /**
   * Starts a transaction and stops autocommits.
   *
   * @return bool True on sucess.
   *
   * @throws \Database\Exceptions\DatabaseConnectionException
   */
  public function transactionBegin() : bool {
    try {
      return $this->pdo->beginTransaction();
    } catch (\Exception $ex) {
      throw DatabaseConnectionException::createSelf($ex->getMessage(), $ex->getCode(), $ex);
    }
  }

  /**
   * Commits a transaction and sets the database back to autocommits.
   *
   * @return bool True on sucess.
   * @throws \Database\Exceptions\DatabaseConnectionException
   */
  public function transactionCommit() : bool {
    try {
      return $this->pdo->commit();
    } catch (\Exception $ex) {
      throw DatabaseConnectionException::createSelf($ex->getMessage(), $ex->getCode(), $ex);
    }
  }

  /**
   * Rolls back a transaction and sets the database back to autocommits.
   *
   * @return bool True on sucess.
   * @throws \Database\Exceptions\DatabaseConnectionException
   */
  public function transactionRollBack() : bool {
    try {
      return $this->pdo->rollBack();
    } catch (\Exception $ex) {
      throw DatabaseConnectionException::createSelf($ex->getMessage(), $ex->getCode(), $ex);
    }
  }

  /**
   * Gets a cached prepared statement for a specific class and statement type. If an statement doesn't exists
   * for that class and statement type, an statement string is obtained from the class, prepared and cached, provided
   * the class returned a valid statement string.
   *
   * @param string $class Class name.
   * @param int    $type  One of the STATEMENT_* constants.
   *
   * @return null|\Database\Main\DatabaseStatement An statement object or null.
   */
  protected function getCachedStatement(string $class, int $type) {
    $class = trim($class, '\\');
    if (!isset($this->statementCache[$class])) {
      $this->statementCache[$class] = [
        self::STATEMENT_SELECT => null,
        self::STATEMENT_DELETE => null,
        self::STATEMENT_UPDATE => null,
        self::STATEMENT_INSERT => null
      ];
    }
    $statement = $this->statementCache[$class][$type];
    if (is_null($statement)) {
      $sql = $class::getSQLStatement($type);
      if (is_string($sql)) {
        $statement = $this->prepare($sql);
        if ($statement !== false) {
          $this->statementCache[$class][$type] = $statement;
        }
      }
    }
    return $statement;
  }

  /**
   * Validates a database object class. Throws an exception if it is not a DatabaseObject class or a descendant class.
   *
   * @param string $class A class name.
   *
   * @return void
   * @throws \Database\Exceptions\DatabaseLogicException
   */
  protected function validateDatabaseObjectClass(string $class) {
    if (!is_a($class, DatabaseObject::class, true)) {
      throw DatabaseLogicException::createSelf('Class "' . $class . '" is not a valid object class');
    }
  }

  /**
   * Returns an instance of a DatabaseObject class filled with corresponding data.
   *
   * @param string $class      Class name of the desired object.
   * @param array  $parameters Statement parameters.
   *
   * @return \Database\Main\DatabaseObject|boolean An instance of the class or False on failure.
   * @throws \Database\Exceptions\DatabaseLogicException
   */
  public function selectObject(string $class, array $parameters) {
    $this->validateDatabaseObjectClass($class);
    $statement = $this->getCachedStatement($class, self::STATEMENT_SELECT);
    if (is_null($statement)) {
      throw DatabaseLogicException::createSelf('No SELECT statement found for "' . $class . '" class');
    }
    $statement->setFetchMode(\PDO::FETCH_CLASS, $class, [$this]);
    try {
      if ($statement->execute($parameters)) {
        $result = $statement->fetch();
        if ($result !== false) {
          $this->classFactory::$dependencyInjector->injectInto($result);
          $result->setState(DatabaseObject::STATE_SYNCED);
        }
      } else {
        $result = false;
      }
    } finally {
      $statement->closeCursor();
    }
    return $result;
  }

  /**
   * Refreshes an object by reassigning values from the database to it's properties.
   *
   * @param \Database\Main\DatabaseObject $object An object.
   *
   * @return bool True on success, False on failure.
   * @throws \Database\Exceptions\DatabaseLogicException
   */
  public function refreshObject(\Database\Main\DatabaseObject $object) : bool {
    if (!$this->isPromiscuousModeEnabled() && in_array(
      $object->getState(),
      [DatabaseObject::STATE_NEW, DatabaseObject::STATE_DELETED, DatabaseObject::STATE_UNINITIALIZED]
    )) {
      throw DatabaseLogicException::createSelf('An object cannot be refreshed if it is new or was already deleted.');
    }
    $class = get_class($object);
    $statement = $this->getCachedStatement($class, self::STATEMENT_SELECT);
    if (is_null($statement)) {
      throw DatabaseLogicException::createSelf('No SELECT statement found for "' . $class . '" class');
    }
    $statement->setFetchMode(\PDO::FETCH_INTO, $object);

    $oldState = $object->getState();
    $object->setState(DatabaseObject::STATE_UNINITIALIZED);

    try {
      if ($statement->execute($object->getStatementParams(self::STATEMENT_SELECT)) && $statement->fetch() !== false) {
        $oldState = DatabaseObject::STATE_SYNCED;
        return true;
      } else {
        return false;
      }
    } finally {
      $statement->closeCursor();
      $object->setState($oldState);
    }
  }

  /**
   * Deletes an object.
   *
   * @param \Database\Main\DatabaseObject $object A DatabaseObject
   *
   * @return boolean|int The number of rows affected or False on failure. Zero means that no errors happened but no
   * rows were affected either, what you may consider as a failure too.
   * @throws \Database\Exceptions\DatabaseLogicException
   */
  public function deleteObject(\Database\Main\DatabaseObject $object) {
    if (!$this->isPromiscuousModeEnabled() && in_array(
      $object->getState(),
      [DatabaseObject::STATE_NEW, DatabaseObject::STATE_DELETED, DatabaseObject::STATE_UNINITIALIZED]
    )) {
      throw DatabaseLogicException::createSelf('An object cannot be deleted if it is new or was already deleted.');
    }
    $class = get_class($object);
    $statement = $this->getCachedStatement($class, self::STATEMENT_DELETE);
    if (is_null($statement)) {
      throw DatabaseLogicException::createSelf('No DELETE statement found for "' . $class . '" class');
    }
    try {
      if ($statement->execute($object->getStatementParams(self::STATEMENT_DELETE))) {
        $object->setState(DatabaseObject::STATE_DELETED);
        return $statement->rowCount();
      } else {
        return false;
      }
    } finally {
      $statement->closeCursor();
    }
  }

  /**
   * Inserts a new object.
   *
   * @param \Database\Main\DatabaseObject $object A DatabaseObject
   *
   * @return boolean|int The number of rows affected or False on failure. Zero means that no errors happened but no
   * rows were affected either, what you may consider as a failure too.
   * @throws \Database\Exceptions\DatabaseLogicException
   */
  public function insertObject(\Database\Main\DatabaseObject $object) {
    if (!$this->isPromiscuousModeEnabled() && $object->getState() != DatabaseObject::STATE_NEW) {
      throw DatabaseLogicException::createSelf('Cannot insert an object that is not new.');
    }
    $class = get_class($object);
    $statement = $this->getCachedStatement($class, self::STATEMENT_INSERT);
    if (is_null($statement)) {
      throw DatabaseLogicException::createSelf('No INSERT statement found for "' . $class . '" class');
    }
    try {
      if ($statement->execute($object->getStatementParams(self::STATEMENT_INSERT))) {
        $object->setState(DatabaseObject::STATE_SYNCED);
        return $statement->rowCount();
      } else {
        return false;
      }
    } finally {
      $statement->closeCursor();
    }
  }

  /**
   * Updates an object data in the database.
   *
   * @param \Database\Main\DatabaseObject $object A DatabaseObject
   *
   * @return boolean|int The number of rows affected or False on failure. Zero means that no errors happened but no
   * rows were affected either, what you may consider as a failure too.
   * @throws \Database\Exceptions\DatabaseLogicException
   */
  public function updateObject(\Database\Main\DatabaseObject $object) {
    if (!$this->isPromiscuousModeEnabled() && $object->getState() != DatabaseObject::STATE_MODIFIED) {
      throw DatabaseLogicException::createSelf('Cannot update an object that is new, was deleted or was not modified.');
    }
    $class = get_class($object);
    $statement = $this->getCachedStatement($class, self::STATEMENT_UPDATE);
    if (is_null($statement)) {
      throw DatabaseLogicException::createSelf('No UPDATE statement found for "' . $class . '" class');
    }
    try {
      if ($statement->execute($object->getStatementParams(self::STATEMENT_UPDATE))) {
        $object->setState(DatabaseObject::STATE_SYNCED);
        return $statement->rowCount();
      } else {
        return false;
      }
    } finally {
      $statement->closeCursor();
    }
  }

  /**
   * Synchronize the database and the object so it reflects the object properties or database values, performing a
   * different action depending on the state of the object.
   *
   * @param \Database\Main\DatabaseObject $object    A DatabaseObject
   * @param bool                          $noRefresh If True, an object with an STATE_SYNCED state will not be refreshed
   *                                                 (have it's properties reassigned from the database).
   *
   * @return boolean|int Depending on the state of the object this method returns True on success, False on
   * failure or the number of affected rows which can be zero to indicate that no error happened but no rows were
   * affected.
   * @throws \Database\Exceptions\DatabaseLogicException
   */
  public function syncObject(\Database\Main\DatabaseObject $object, bool $noRefresh = true) {
    if ($this->isPromiscuousModeEnabled()) {
      throw DatabaseLogicException::createSelf('An object cannot be synced in promiscuous mode.');
    }
    switch ($object->getState()) {
      case DatabaseObject::STATE_NEW:
        return $this->insertObject($object);
      case DatabaseObject::STATE_MODIFIED:
        return $this->updateObject($object);
      case DatabaseObject::STATE_SYNCED:
        return $noRefresh ? true : $this->refreshObject($object);
      default:
        return false;
    }
  }

  /**
   * Returns an instance of the DatabaseCollection class containing DatabaseObject instances for each of the resultset
   * rows.
   *
   * @param string $class      Class name of the desired collection.
   * @param array  $parameters Statement parameters.
   *
   * @return \Database\Main\DatabaseCollection|boolean An instance of the class or False on failure.
   * @throws \Database\Exceptions\DatabaseLogicException
   */
  public function selectCollection(string $class, array $parameters) {
    if (!is_a($class, DatabaseCollection::class, true)) {
      throw DatabaseLogicException::createSelf('Class "' . $class . '" is not a valid collection class');
    }

    $itemsClass = $class::getItemsClass();
    $this->validateDatabaseObjectClass($itemsClass);

    $statement = $this->getCachedStatement($class, self::STATEMENT_SELECT);
    if (is_null($statement)) {
      throw DatabaseLogicException::createSelf('No SELECT statement found for "' . $class . '" class');
    }
    $statement->setFetchMode(\PDO::FETCH_CLASS, $itemsClass, [$this]);
    try {
      if ($statement->execute($parameters)) {
        $items = $statement->fetchAll();
        if ($items !== false) {
          foreach ($items as $item) {
            $this->classFactory::$dependencyInjector->injectInto($item);
            $item->setState(DatabaseObject::STATE_SYNCED);
          }
          $collection = $this->classFactory::create($class, $this);
          $collection->addArray($items);
          return $collection;
        } else {
          return false;
        }
      } else {
        $result = false;
      }
    } finally {
      $statement->closeCursor();
    }
    return $result;
  }

  /**
   * Deletes all items of a collection. If all items have been deleted, the collection and the failed paramaeter will
   * both be empty, otherwise the non deleted items will be reassigned to the collection.
   *
   * @param \Database\Main\DatabaseCollection $collection A DatabaseCollection
   * @param array                             $failed     A reference to an array that will contain items that failed
   *                                                      to be deleted and the corresponding exception (if any). Optional.
   *
   * @return int The number of deleted items.
   * @throws \Database\Exceptions\DatabaseConnectionException
   */
  public function deleteCollection(\Database\Main\DatabaseCollection $collection, array &$failed = null) : int {
    if (!is_null($failed)) {
      $failed = [];
    }
    while ($collection->count() > 0) {
      $collection->remove(0);
    }
    $items = $collection->getRemovedItems();
    $countBefore = count($items);
    $collTrans = $this->collectionTransactionsEnabled() ? $this->transactionBegin() : false;
    try {
      foreach ($items as $object) {
        try {
          if ($this->deleteObject($object)) {
            $collection->dispose($object);
          } else {
            $collection->add($object);
            if (!is_null($failed)) {
              $failed[] = ['item' => $object, 'exception' => null];
            }
          }
        } catch (DatabaseException $ex) {
          if ($ex instanceof DatabaseConnectionException) {
            throw $ex;
          }
          $collection->add($object);
          if (!is_null($failed)) {
            $failed[] = ['item' => $object, 'exception' => $ex];
          }
        }
      }
    } finally {
      if ($collTrans) {
        $this->transactionCommit();
      }
    }
    $countAfter = $collection->count();
    return $countBefore - $countAfter;
  }

  /**
   * Inserts all items from a collection in the database. If all items have been inserted the returned value will be
   * equal to the item count and the failed paramaeter will be empty.
   *
   * @param \Database\Main\DatabaseCollection $collection A DatabaseCollection
   * @param array                             $failed     A reference to an array that will contain items that failed
   *                                                      to be inserted and the corresponding exception (if any). Optional.
   *
   * @return int The number of inserted items.
   * @throws \Database\Exceptions\DatabaseConnectionException
   */
  public function insertCollection(\Database\Main\DatabaseCollection $collection, array &$failed = null) : int {
    if (!is_null($failed)) {
      $failed = [];
    }
    $inserted = 0;
    $collTrans = $this->collectionTransactionsEnabled() ? $this->transactionBegin() : false;
    try {
      foreach ($collection as $object) {
        try {
          if ($this->insertObject($object)) {
            $inserted++;
          } elseif (!is_null($failed)) {
            $failed[] = ['item' => $object, 'exception' => null];
          }
        } catch (DatabaseException $ex) {
          if ($ex instanceof DatabaseConnectionException) {
            throw $ex;
          }
          if (!is_null($failed)) {
            $failed[] = ['item' => $object, 'exception' => $ex];
          }
        }
      }
    } finally {
      if ($collTrans) {
        $this->transactionCommit();
      }
    }
    return $inserted;
  }

  /**
   * Updates the corresponding data in the database of all items from a collection. If all items have been updated the
   * returned value will be equal to the item count and the failed paramaeter will be empty.
   *
   * @param \Database\Main\DatabaseCollection $collection A DatabaseCollection
   * @param array                             $failed     A reference to an array that will contain items that failed
   *                                                      to be updated and the corresponding exception (if any). Optional.
   *
   * @return int The number of updated items.
   * @throws \Database\Exceptions\DatabaseConnectionException
   */
  public function updateCollection(\Database\Main\DatabaseCollection $collection, array &$failed = null) : int {
    if (!is_null($failed)) {
      $failed = [];
    }
    $updated = 0;
    $collTrans = $this->collectionTransactionsEnabled() ? $this->transactionBegin() : false;
    try {
      foreach ($collection as $object) {
        try {
          if ($this->updateObject($object)) {
            $updated++;
          } elseif (!is_null($failed)) {
            $failed[] = ['item' => $object, 'exception' => null];
          }
        } catch (DatabaseException $ex) {
          if ($ex instanceof DatabaseConnectionException) {
            throw $ex;
          }
          if (!is_null($failed)) {
            $failed[] = ['item' => $object, 'exception' => $ex];
          }
        }
      }
    } finally {
      if ($collTrans) {
        $this->transactionCommit();
      }
    }
    return $updated;
  }

  /**
   * Synchronize the database so it reflects the properties of all items in the collection, performing a different
   * action depending on the state of each item. If all items have been synced the returned value will be equal to the
   * item count and the failed paramaeter will be empty.
   *
   * @param \Database\Main\DatabaseCollection $collection A DatabaseCollection
   * @param array                             $failed     A reference to an array that will contain items that failed to
   *                                                      be synced and the corresponding exception (if any). Optional.
   * @param bool                              $noRefresh  If True, an object with an STATE_SYNCED state will not be
   *                                                      refreshed (have it's properties reassigned from the database).
   *                                                      Optional.
   *
   * @return int The number of synced items.
   * @throws \Database\Exceptions\DatabaseConnectionException
   */
  public function syncCollection(
    \Database\Main\DatabaseCollection $collection, array &$failed = null, bool $noRefresh = true
  ) : int {
    if ($this->isPromiscuousModeEnabled()) {
      throw DatabaseLogicException::createSelf('A collection cannot be synced in promiscuous mode.');
    }
    if (!is_null($failed)) {
      $failed = [];
    }
    $synced = 0;
    $collTrans = $this->collectionTransactionsEnabled() ? $this->transactionBegin() : false;
    try {
      foreach ($collection as $object) {
        try {
          if ($this->syncObject($object, $noRefresh)) {
            $synced++;
          } elseif (!is_null($failed)) {
            $failed[] = ['item' => $object, 'exception' => null];
          }
        } catch (DatabaseException $ex) {
          if ($ex instanceof DatabaseConnectionException) {
            throw $ex;
          }
          if (!is_null($failed)) {
            $failed[] = ['item' => $object, 'exception' => $ex];
          }
        }
      }
    } finally {
      if ($collTrans) {
        $this->transactionCommit();
      }
    }
    return $synced;
  }

  /**
   * Creates a new instance of a DatabaseClass descendant, generally a DatabaseObject or DatabaseCollection.
   *
   * @param string $class    Class name
   * @param mixed  ...$extra Any number of parameters of any kind, passed to the constructor.
   *
   * @return \Database\Main\DatabaseClass
   * @throws \Database\Exceptions\DatabaseLogicException
   */
  public function newObject(string $class, ...$extra) : \Database\Main\DatabaseClass {
    if (is_a($class, 'Database\\Main\\DatabaseClass', true)) {
      $newObj = $this->classFactory::create($class, $this, ...$extra);
      if (($newObj instanceof DatabaseObject) && ($newObj->getState() != DatabaseObject::STATE_NEW)) {
        $newObj->setState(DatabaseObject::STATE_NEW);
      }
      return $newObj;
    } else {
      throw DatabaseLogicException::createSelf('Class "' . $class . '" is not a valid DatabaseClass');
    }
  }

  /**
   * Alias of newObject.
   *
   * @param string $class    Class name
   * @param mixed  ...$extra Any number of parameters of any kind, passed to the constructor.
   *
   * @return \Database\Main\DatabaseClass
   * @throws \Database\Exceptions\DatabaseLogicException
   */
  public function newCollection(string $class, ...$extra) : \Database\Main\DatabaseClass {
    return $this->newObject($class, ...$extra);
  }

}
