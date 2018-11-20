<?php

/**
 * ReadOnlyDBObject class
 *
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Database\Main;

use Database\Main\DatabaseObject;

/**
 * Object used only for selections.
 *
 * @package Database
 */
class ReadOnlyDBObject extends DatabaseObject {

  /**
   * Returns a DML statement for this object. This specific class has no DML statements.
   *
   * @param int $type One the DatabaseConnection::STATEMENT_* constants. Ignored in this class.
   *
   * @return null Always null for this class.
   */
  public static function getSQLStatement(int $type) {
    return null;
  }

  /**
   * Returns the values for statement parameters. Always empty.
   *
   * @param int $type One the DatabaseConnection::STATEMENT_* constants. Ignored in this class.
   *
   * @return array Always an empty array for this class.
   */
  public function getStatementParams(int $type) : array {
    return [];
  }

}
