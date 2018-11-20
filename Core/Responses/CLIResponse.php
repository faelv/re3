<?php

/**
 * CLIResponse class
 *
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Responses;

use Core\Responses\Response;
use Core\Application\MagicInjection;

/**
 * Response class for the CLI.
 *
 * @package Core
 */
class CLIResponse extends Response {

  use MagicInjection;

  /**
   * Stores the response output text.
   * @var string
   */
  protected $text = '';

  /**
   * Sets the text that this response will output.
   *
   * @param string $text A string.
   *
   * @return void
   */
  public function setText(string $text) {
    $this->text = $text;
  }

  /**
   * Returns the current text
   *
   * @return string
   */
  public function getText() : string {
    return $this->text;
  }

  /**
   * Appends a string to the end of the current text.
   *
   * @param string $text A string.
   *
   * @return void
   */
  public function appendText(string $text) {
    $this->text .= $text;
  }

  /**
   * Appends a string to the end of the current text, automatically inserting a line break first.
   *
   * @param string $line A string.
   *
   * @return void
   */
  public function appendLine(string $line) {
    $this->appendText(($this->text == '') ? $line : PHP_EOL . $line);
  }

  /**
   * Appends new lines to the end of the current text.
   *
   * @param int $number The number of new lines.
   *
   * @return void
   */
  public function appendNewLine(int $number = 1) {
    $this->appendText(str_repeat(PHP_EOL, $number));
  }

  /**
   * Outputs the content.
   *
   * @return void
   */
  public function output() {
    echo $this->text;
  }

  /**
   * Flushes the current content text by sending it to the output.
   *
   * @return void
   */
  public function flush() {
    $this->output();
    $this->setText('');
    flush();
  }

}
