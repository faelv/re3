<?php

/**
 * DatabaseStatement class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Database\Main;

/**
 * Prepared statement class.
 *
 * @package Database
 */
class DatabaseStatement extends \PDOStatement {

  /**
   * Stores a reference to the connection object.
   * @var \Database\Main\DatabaseConnection
   */
  protected $connection = null;

  /**
   * Log enabled flag.
   * @var boolean
   */
  static protected $logging = false;

  /**
   * DateTime format for conversion to string.
   * @var string
   */
  static protected $dateTimeFormat = 'Y-m-d H:i:s';

  /**
   * Values used to convert booleans.
   *
   * @var array
   */
  static protected $boolValues = [true => 1, false => 0];

  /**
   * Constructor.
   *
   * @param \Database\Main\DatabaseConnection $connection A reference to the connection.
   */
  protected function __construct(\Database\Main\DatabaseConnection $connection) {
    $this->connection = $connection;
  }

  public function __destruct() {
    $this->connection = null;
  }

  /**
   * Enables or disables logging.
   *
   * @param bool $enabled True to enable, False to disable.
   *
   * @return void
   */
  public static function setLogging(bool $enabled) {
    self::$logging = $enabled;
  }

  /**
   * Sets the date and time format for automatic DateTimeInterface conversion.
   *
   * @param string $format A date and time format.
   *
   * @return void
   */
  public static function setDateTimeFormat(string $format) {
    self::$dateTimeFormat = $format;
  }

  /**
   * Gets the current date and time format.
   *
   * @return string The date and time format.
   */
  public static function getDateTimeFormat() : string {
    return self::$dateTimeFormat;
  }

  /**
   * Sets the values whose booleans will be converted to.
   *
   * @param mixed $trueValue  Value used for True
   * @param mixed $falseValue Value used for False
   *
   * @return void
   */
  public static function setBoolValues($trueValue, $falseValue) {
    self::$boolValues[true] = $trueValue;
    self::$boolValues[false] = $falseValue;
  }

  /**
   * Gets the current boolean conversion values.
   *
   * @return array An array with two elements, the element with the True key stores the value for True, the element with
   * the False key stores the value for False.
   */
  public static function getBoolValues() : array {
    return self::$boolValues;
  }

  /**
   * Inherited execute method.
   *
   * @param array $input_parameters Input parameters.
   *
   * @return boolean
   */
  public function execute($input_parameters = null) {
    if (self::$logging) {
      trigger_error(
        '[PREPARED STATEMENT -> EXECUTE]' . PHP_EOL .
        'QUERY  => ' . $this->queryString . PHP_EOL .
        'PARAMS => ' . print_r($input_parameters, true),
        E_USER_NOTICE
      );
    }
    foreach ($input_parameters as &$param) {
      if ($param instanceof \DateTimeInterface) {
        $param = $param->format(self::$dateTimeFormat);
      } elseif (is_bool($param)) {
        $param = $param ? self::$boolValues[true] : self::$boolValues[false];
      }
    }
    return parent::execute($input_parameters);
  }

}
