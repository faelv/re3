<?php

/**
 * Logger class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Application;

/**
 * Class responsable for the logging system.
 *
 * @package Core
 */
class Logger {

  /**
   * General purpose log event.
   */
  const EVENT_LOG = 0;

  /**
   * A debug log event.
   */
  const EVENT_DEBUG = 1;

  /**
   * An event for minor informations.
   */
  const EVENT_INFO = 2;

  /**
   * An event that should be investigated.
   */
  const EVENT_WARNING = 3;

  /**
   * An event for critical or fatal errors.
   */
  const EVENT_ERROR = 4;

  /**
   * OSD mode to use HTML.
   */
  const OSD_MODE_HTML = 10;

  /**
   * OSD mode to use plain text.
   */
  const OSD_MODE_TEXT = 11;

  /**
   * Stores the OSD output mode.
   * @var int
   */
  protected $osdMode = self::OSD_MODE_HTML;

  /**
   * Stores events that will be displayed to the user.
   * @var array
   */
  protected $osdEvents = [];

  /**
   * Stores the event types that will be displayed to the user.
   * @var array
   */
  protected $osdEventTypes;

  /**
   * Stores the path to the log output file.
   * @var string
   */
  protected $outputFile = '';

  /**
   * Stores the names of the event constants. The keys are the constants values.
   * @var array
   */
  protected $eventNames = [];

  /**
   * Gets the script memory usage.
   *
   * @param bool $peak Pass True to get the peak usage instead of the current usage.
   * @return int The memory usage in bytes.
   */
  protected function getMemoryUsage(bool $peak = false) : int {
    if ($peak) {
      return memory_get_peak_usage();
    } else {
      return memory_get_usage();
    }
  }

  /**
   * Formats an event description.
   *
   * @param int           $eventType   One of the EVENT_* constants.
   * @param string|object $description If an object is used the method __toString will be called.
   *
   * @return string The formatted description.
   */
  protected function formatEventDescription(int $eventType, $description) : string {
    if (!is_string($description)) {
      $description = (string)$description;
    }
    $eventName = isset($this->eventNames[$eventType]) ? $this->eventNames[$eventType] : 'UNKNOW';
    $memory = nice_bytes($this->getMemoryUsage());
    $time = microsec_now();

    return 'EVENT: ' . $eventName . ' / TIME: ' . $time . ' / MEMORY: ' . $memory . PHP_EOL . $description;
  }

  /**
   * Add a new OSD event to the internal OSD events array.
   *
   * @param int           $eventType   One of the EVENT_* constants.
   * @param string|object $description A string or a object that can be represented as a string.
   *
   * @return void
   */
  protected function storeOSDEvent(int $eventType, $description) {
    $this->osdEvents[] = [
      'type' => $eventType,
      'description' => $this->formatEventDescription($eventType, $description)
    ];
  }

  /**
   * Appends an event to the log output file.
   *
   * @param int           $eventType   One of the EVENT_* constants.
   * @param string|object $description A string or a object that can be represented as a string.
   *
   * @return bool True on success
   */
  protected function appendToLogFile(int $eventType, $description) : bool {
    if (!empty($this->outputFile)) {
      return false !== file_put_contents(
        $this->outputFile,
        PHP_EOL . $this->formatEventDescription($eventType, $description) . PHP_EOL,
        FILE_APPEND
      );
    }
    return false;
  }

  /**
   * Constructor. Optionally sets the OSD event types and log file.
   *
   * @param array  $osdEventTypes An array of EVENT_* constants.
   * @param string $outputFile    Path to the log output file.
   *
   * @return void
   */
  public function __construct(array $osdEventTypes = null, string $outputFile = null) {
    $reflection = new \ReflectionClass($this);
    $constants = $reflection->getConstants();
    foreach ($constants as $name => $value) {
      if (substr_compare($name, 'EVENT_', 0, 6) === 0) {
        $this->eventNames[$value] = str_replace('_', ' ', substr($name, 6));
      }
    }

    if (is_array($osdEventTypes)) {
      $this->setOSDEventTypes($osdEventTypes);
    } else {
      $this->setOSDEventTypes([self::EVENT_ERROR, self::EVENT_WARNING, self::EVENT_INFO, self::EVENT_DEBUG]);
    }

    if (!is_null($outputFile)) {
      $this->setOutputFile($outputFile);
    }
  }

  /**
   * Sets the OSD output mode.
   *
   * @param int $mode One of the OSD_MODE_* constants.
   *
   * @return void
   */
  public function setOSDMode(int $mode) {
    $this->osdMode = $mode;
  }

  /**
   * Returns the current OSD output mode.
   *
   * @return int One of the OSD_MODE_* constants.
   */
  public function getOSDMode() : int {
    return $this->osdMode;
  }

  /**
   * Sets the event types that will be displayed to the user.
   *
   * @param array $types An array of EVENT_* constants.
   *
   * @return void
   */
  public function setOSDEventTypes(array $types) {
    $this->osdEventTypes = $types;
  }

  /**
   * Returns the current OSD event types.
   *
   * @return array An array of EVENT_* constants.
   */
  public function getOSDEventTypes() : array {
    return $this->osdEventTypes;
  }

  /**
   * Disables OSD events logging. Same as passing an empty array to setOSDEventTypes method.<br>
   * You can still find logs in the log file.
   *
   * @return void
   */
  public function disableOSD() {
    $this->setOSDEventTypes([]);
  }

  /**
   * Sets the log output file.
   *
   * @param string $filename Filename
   *
   * @return void
   */
  public function setOutputFile(string $filename) {
    $this->outputFile = $filename;
  }

  /**
   * Returns the current log output file.
   *
   * @return string Filename
   */
  public function getOutputFile() : string {
    return $this->outputFile;
  }

  /**
   * Logs an event.
   *
   * @param int           $eventType   One of the EVENT_* constants.
   * @param string|object $description A string or a object that can be represented as a string.
   *
   * @return void
   */
  public function log(int $eventType, $description) {
    if (in_array($eventType, $this->osdEventTypes)) {
      $this->storeOSDEvent($eventType, $description);
    }
    $this->appendToLogFile($eventType, $description);
  }

  /**
   * Outputs log event information on screen in plain text format.
   *
   * @return void
   */
  protected function outputTextOSD() {
    $evtCount = count($this->osdEvents);
    $memory = nice_bytes($this->getMemoryUsage(true));
    if ($evtCount > 0) {
      $result  = str_repeat('*', 20) . PHP_EOL;
      $result .= 'LOG EVENTS:  ' . $evtCount . PHP_EOL .
                 'LOG FILE:    ' . $this->getOutputFile() . PHP_EOL .
                 'PEAK MEMORY: ' . $memory;

      foreach ($this->osdEvents as $event) {
        $result .= PHP_EOL . PHP_EOL . $event['description'];
      }

      $result .= PHP_EOL . str_repeat('*', 20);
      echo $result;
    }
  }

  /**
   * Outputs log event information on screen in HTML format.
   *
   * @return void
   */
  protected function outputHtmlOSD() {
    $evtCount = count($this->osdEvents);
    $memory = nice_bytes($this->getMemoryUsage(true));
    if ($evtCount > 0) {
      $result = <<<HTML
<div style="
  font-family: 'Lucida Console', Monaco, monospace; border-radius: 3px; color: #333333;
  font-size: 14px; margin: 8px; border: solid 1px #7b7b7b; background-color: #f8f8f8;
  box-shadow: 0 0 5px #ccc;
">
  <div style="padding: 8px;">
  <b>LOG EVENTS:</b> $evtCount<br />
  <b>LOG FILE:</b> {$this->getOutputFile()}<br />
  <b>PEAK MEMORY:</b> $memory
  </div>
<div style="padding: 8px;">
HTML;

      foreach ($this->osdEvents as $event) {
        switch ($event['type']) {
          case self::EVENT_ERROR:
            $evtColor = '#ff6666';
            break;
          case self::EVENT_WARNING:
            $evtColor = '#ffa366';
            break;
          case self::EVENT_INFO:
            $evtColor = '#339cff';
            break;
          default:
            $evtColor = '#a6a6a6';
        }
        $evtName = isset($this->eventNames[$event['type']]) ? $this->eventNames[$event['type']] : 'UNKNOW';

        $result .= <<<EVENT
<div style="background-color: white; margin: 4px 0 4px 0; border-radius: 3px; border: solid 1px $evtColor;">
  <div style="padding: 3px; border-bottom: inherit; background-color: $evtColor;"><b>$evtName</b></div>
EVENT;

        $result .= '<pre style="padding: 3px; margin: 0;">' . $event['description'] . '</pre></div>';
      }

      $result .= '</div></div>';
      echo $result;
    }
  }

  /**
   * Outputs log event information on screen.
   *
   * @return void
   */
  public function outputOSD() {
    if ($this->osdMode == self::OSD_MODE_HTML) {
      $this->outputHtmlOSD();
    } else {
      $this->outputTextOSD();
    }
  }

}
