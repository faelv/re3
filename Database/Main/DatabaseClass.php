<?php

/**
 * DatabaseClass class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */


namespace Database\Main;

/**
 * Base class for database objects.
 *
 * @package Database
 */
abstract class DatabaseClass {

  /**
   * Stores a reference to the connection object.
   * @var \Database\Main\DatabaseConnection
   */
  protected $connection = null;

  /**
   * Constructor.
   *
   * @param \Database\Main\DatabaseConnection $connection A reference to the connection.
   */
  public function __construct(\Database\Main\DatabaseConnection $connection) {
    $this->connection = $connection;
  }

}
