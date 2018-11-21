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
   * Tells if colors and formatting are enabled in the output.
   * @var bool
   */
  protected $colorsEnabled = true;

  /**
   * Color and formatting to control sequence mappings.
   * @var array
   */
  protected $colorMappings = [
    'foreground' => [
      'default' => '39',
      'black' => '30',
      'red' => '31',
      'green' => '32',
      'yellow' => '33',
      'blue' => '34',
      'magenta' => '35',
      'cyan' => '36',
      'lightgray' => '37',
      'darkgray' => '90',
      'lightred' => '91',
      'lightgreen' => '92',
      'lightyellow' => '93',
      'lightblue' => '94',
      'lightmagenta' => '95',
      'lightcyan' => '96',
      'white' => '97',

      'd' => '39',
      'blk' => '30',
      'r' => '31',
      'g' => '32',
      'y' => '33',
      'b' => '34',
      'm' => '35',
      'c' => '36',
      'lgy' => '37',
      'dgy' => '90',
      'lr' => '91',
      'lg' => '92',
      'ly' => '93',
      'lb' => '94',
      'lm' => '95',
      'lc' => '96',
      'w' => '97'
    ],
    'background' => [
      'default' => '49',
      'black' => '40',
      'red' => '41',
      'green' => '42',
      'yellow' => '43',
      'blue' => '44',
      'magenta' => '45',
      'cyan' => '46',
      'lightgray' => '47',
      'darkgray' => '100',
      'lightred' => '101',
      'lightgreen' => '102',
      'lightyellow' => '103',
      'lightblue' => '104',
      'lightmagenta' => '105',
      'lightcyan' => '106',
      'white' => '107',

      'd' => '49',
      'blk' => '40',
      'r' => '41',
      'g' => '42',
      'y' => '43',
      'b' => '44',
      'm' => '45',
      'c' => '46',
      'lgy' => '47',
      'dgy' => '100',
      'lr' => '101',
      'lg' => '102',
      'ly' => '103',
      'lb' => '104',
      'lm' => '105',
      'lc' => '106',
      'w' => '107'
    ],
    'style' => [
      'bold' => '1',
      'dim' => '2',
      'italic' => '3',
      'underline' => '4',
      'inverted' => '7',
      'hidden' => '8',

      'b' => '1',
      'd' => '2',
      'i' => '3',
      'u' => '4',
      'v' => '7',
      'h' => '8'
    ],
    'reset' => '0'
  ];

  /**
   * Replaces color tags with the corresponding bash formatting control sequence.
   *
   * @param string $text Text to parse.
   *
   * @return string Parsed text.
   */
  protected function parseColorTags(string $text) : string {
    $text = mb_ereg_replace('</color>', "\e[{$this->colorMappings['reset']}m", $text);

    $text = mb_ereg_replace_callback(
      '<color +(.*?)>',
      function ($matches) {
        $colors = explode(' ', $matches[1], 3);

        $foreground = count($colors) >= 1 ? $colors[0] : 'null';
        $background = count($colors) >= 2 ? $colors[1] : 'null';
        $style = count($colors) >= 3 ? $colors[2] : 'null';

        $foreground = array_get_if_set($this->colorMappings['foreground'], $foreground);
        $background = array_get_if_set($this->colorMappings['background'], $background);
        $style = array_get_if_set($this->colorMappings['style'], $style);

        $result = "\e[";
        $result .= ($style ? "$style;" : '') . ($foreground ? "$foreground;" : '') . ($background ? "$background;" : '');
        return rtrim($result, ';') . 'm';
      },
      $text
    );

    return $text;
  }

  /**
   * Enables or disables colors and formatting in the output.
   *
   * @param bool $enabled True to enable, False to disable.
   *
   * @return void
   */
  public function setColorsEnabled(bool $enabled) {
    $this->colorsEnabled = $enabled;
  }

  /**
   * Returns the current colors and formatting enabled flag value.
   *
   * @return bool
   */
  public function getColorsEnabled() : bool {
    return $this->colorsEnabled;
  }

  /**
   * Sets the text that this response will output. The text can be formatted, see the output method.
   *
   * @param string $text A string.
   *
   * @return void
   * @see \Core\Responses\CLIResponse->output()
   */
  public function setText(string $text) {
    $this->text = $text;
  }

  /**
   * Returns the current text.
   *
   * @return string
   */
  public function getText() : string {
    return $this->text;
  }

  /**
   * Appends a string to the end of the current text. The text can be formatted, see the output method.
   *
   * @param string $text A string.
   *
   * @return void
   * @see \Core\Responses\CLIResponse->output()
   */
  public function appendText(string $text) {
    $this->text .= $text;
  }

  /**
   * Appends a string to the end of the current text, automatically inserting a line break first. The text can be
   * formatted, see the output method.
   *
   * @param string $line A string.
   *
   * @return void
   * @see \Core\Responses\CLIResponse->output()
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
   * Outputs the content. The output can be formatted by using color tags, a color tag is like this:
   * <color foreground [background] [style]> and the formatting can be terminated with the closing (or reset) tag
   * </color>.
   *
   * Replace 'foreground' with one of the following values: default, black, red, green, yellow, blue, magenta, cyan,
   * lightgray, darkgray, lightred, lightgreen, lightyellow, lightblue, lightmagenta, lightcyan, white, null.
   *
   * Replace 'background' with one of the following values: default, black, red, green, yellow, blue, magenta, cyan,
   * lightgray, darkgray, lightred, lightgreen, lightyellow, lightblue, lightmagenta, lightcyan, white, null.
   *
   * Replace 'style' with one of the following values: bold, dim, italic, underline, inverted, hidden, null.
   *
   * To ignore (not set) a color or style use 'null' (or '-') instead of a color or style value.
   *
   * Short versions are also available: default=d, black=blk, red=r, green=g, yellow=y, blue=b, magenta=m, cyan=c,
   * lightgray=lgy, darkgray=dgy, lightred=lr, lightgreen=lg, lightyellow=ly, lightblue=lb, lightmagenta=lm,
   * lightcyan=lc, white=w, bold=b, dim=d, italic=i, underline=u, inverted=v, hidden=h
   *
   * @return void
   */
  public function output() {
    echo $this->getColorsEnabled() ? $this->parseColorTags($this->getText()) : $this->getText();
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

  /**
   * Clears output's last line.
   *
   * @return void
   */
  public function clearLine() {
    echo "\e[2K\r";
  }

  /**
   * Clears the terminal screen.
   *
   * @return void
   */
  public function clearScreen() {
    echo "\e[2J\e[;H";
  }

}
