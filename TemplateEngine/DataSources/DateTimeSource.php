<?php

/**
 * DateTimeSource class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace TemplateEngine\DataSources;

use TemplateEngine\DataSources\DataSource;

/**
 * Data source class for DateTime.
 *
 * @package TemplateEngine
 */
class DateTimeSource extends DataSource {

  /**
   * The date object
   * @var \DateTime
   */
  protected $date = null;

  /**
   * A default date format if the format was not set.
   * @var string
   */
  protected $defaultFormat = DATE_RFC850;

  /**
   * The date format
   * @var string
   */
  protected $format = '';

  /**
   * Constructor.
   *
   * @param \DateTime $date   The date object
   * @param string    $format the date format
   */
  public function __construct(\DateTime $date = null, string $format = null) {
    $this->setDate($date);
    $this->setFormat($format);
  }

  /**
   * Sets the date.
   *
   * @param \DateTime $date The date object
   *
   * @return void
   */
  public function setDate(\DateTime $date) {
    $this->date = $date;
  }

  /**
   * Sets the date format.
   *
   * @param string $format The date format
   *
   * @return void
   */
  public function setFormat(string $format) {
    $this->format = $format;
  }

  /**
   * Returns the date.
   *
   * @return \DateTime
   */
  public function getDate() : \DateTime {
    return $this->date;
  }

  /**
   * Returns the date format.
   *
   * @return string
   */
  public function getFormat() : string {
    return $this->format;
  }

  /**
   * Returns a string representation of the data of this source.
   *
   * @return string
   */
  public function __toString() : string {
    if (!is_null($this->date)) {
      return $this->date->format((empty($this->format) ? $this->defaultFormat : $this->format));
    }
    return 'NULL';
  }

}
