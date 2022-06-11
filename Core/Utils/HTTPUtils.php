<?php

/**
 * HTTPUtils class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Utils;

/**
 * Class with utility methods related to the HTTP protocol.
 *
 * @package Core
 */
class HTTPUtils {

  const STATUS_OK                    = 200;
  const STATUS_PARTIAL_CONTENT       = 206;
  const STATUS_MOVED_PERMANENTLY     = 301;
  const STATUS_FOUND                 = 302;
  const STATUS_SEE_OTHER             = 303;
  const STATUS_NOT_MODIFIED          = 304;
  const STATUS_TEMPORARY_REDIRECT    = 307;
  const STATUS_PERMANENT_REDIRECT    = 308;
  const STATUS_BAD_REQUEST           = 400;
  const STATUS_FORBIDDEN             = 403;
  const STATUS_NOT_FOUND             = 404;
  const STATUS_INTERNAL_SERVER_ERROR = 500;
  const STATUS_SERVICE_UNAVAILABLE   = 503;

  /**
   * HTTP protocol date format
   */
  const HTTP_DATE_FORMAT = 'D, d M Y H:i:s \G\M\T';

  /**
   * Names of the STATUS_* constants
   * @var array
   */
  private $statusStrings = [];

  /**
   * Default MIME type if a specific type is unknow
   * @var string
   */
  protected $defaultMIMEType = 'application/octet-stream';

  /**
   * Provides mappings of file extensions to mimetypes.
   * Based on the one used in Facebook SDK which in turn was taken from Guzzle.
   * @var array
   * @see https://github.com/guzzle/guzzle/blob/master/src/Mimetypes.php
   * @see http://svn.apache.org/repos/asf/httpd/httpd/branches/1.3.x/conf/mime.types
   */
  protected $MIMETypes = [
    '7z'      => 'application/x-7z-compressed',
    'aac'     => 'audio/x-aac',
    'apng'    => 'image/apng',
    'atom'    => 'application/atom+xml',
    'avif'    => 'image/avif',
    'bmp'     => 'image/bmp',
    'bz'      => 'application/x-bzip',
    'bz2'     => 'application/x-bzip2',
    'css'     => 'text/css',
    'csv'     => 'text/csv',
    'doc'     => 'application/msword',
    'docx'    => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'epub'    => 'application/epub+zip',
    'eot'     => 'application/vnd.ms-fontobject',
    'flv'     => 'video/x-flv',
    'gif'     => 'image/gif',
    'h264'    => 'video/h264',
    'htm'     => 'text/html',
    'html'    => 'text/html',
    'ico'     => 'image/x-icon',
    'jpeg'    => 'image/jpeg',
    'jpg'     => 'image/jpeg',
    'js'      => 'text/javascript',
    'json'    => 'application/json',
    'log'     => 'text/plain',
    'm2v'     => 'video/mpeg',
    'm3a'     => 'audio/mpeg',
    'm4a'     => 'audio/mp4',
    'm4v'     => 'video/mp4',
    'mid'     => 'audio/midi',
    'midi'    => 'audio/midi',
    'mov'     => 'video/quicktime',
    'mp3'     => 'audio/mpeg',
    'mp4'     => 'video/mp4',
    'mp4a'    => 'audio/mp4',
    'mp4v'    => 'video/mp4',
    'mpeg'    => 'video/mpeg',
    'mpg'     => 'video/mpeg',
    'oga'     => 'audio/ogg',
    'ogg'     => 'audio/ogg',
    'ogv'     => 'video/ogg',
    'otf'     => 'application/x-font-otf',
    'pdf'     => 'application/pdf',
    'png'     => 'image/png',
    'ppt'     => 'application/vnd.ms-powerpoint',
    'pptx'    => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'qt'      => 'video/quicktime',
    'rar'     => 'application/x-rar-compressed',
    'rss'     => 'application/rss+xml',
    'svg'     => 'image/svg+xml',
    'svgz'    => 'image/svg+xml',
    'swf'     => 'application/x-shockwave-flash',
    'tar'     => 'application/x-tar',
    'text'    => 'text/plain',
    'torrent' => 'application/x-bittorrent',
    'ttf'     => 'application/x-font-ttf',
    'txt'     => 'text/plain',
    'wav'     => 'audio/x-wav',
    'webm'    => 'video/webm',
    'webp'    => 'image/webp',
    'wma'     => 'audio/x-ms-wma',
    'wmv'     => 'video/x-ms-wmv',
    'woff'    => 'application/x-font-woff',
    'woff2'   => 'application/x-font-woff2',
    'xhtml'   => 'application/xhtml+xml',
    'xls'     => 'application/vnd.ms-excel',
    'xlsx'    => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'xml'     => 'application/xml',
    'xps'     => 'application/vnd.ms-xpsdocument',
    'xsl'     => 'application/xml',
    'zip'     => 'application/zip',
  ];

  /**
   * Constructor.
   */
  public function __construct() {
    $reflection = new \ReflectionClass($this);
    $constants = $reflection->getConstants();
    foreach ($constants as $name => $value) {
      if (substr_compare($name, 'STATUS_', 0, 7) === 0) {
        $this->statusStrings[$value] = str_replace('_', ' ', substr($name, 7));
      }
    }
  }

  /**
   * Returns a string representation of a HTTP status code.
   *
   * @param int $code One the STATUS_* constants.
   *
   * @return string
   */
  public function statusStringFromCode(int $code) : string {
    return isset($this->statusStrings[$code]) ? $this->statusStrings[$code] : 'UNKNOW';
  }

  /**
   * Gets a MIME type for a file extension.
   *
   * @param string $extension  A file extension.
   * @param bool   $useDefault True if the default MIME type should be used when the extension is unknow.
   *
   * @return string|boolean Returns the MIME type or False if it's unknow and useDefault is False.
   */
  public function MIMETypeFromExtension(string $extension, bool $useDefault = true) {
    $extension = strtolower($extension);
    if (isset($this->MIMETypes[$extension])) {
      return $this->MIMETypes[$extension];
    } elseif ($useDefault) {
      return $this->defaultMIMEType;
    } else {
      return false;
    }
  }

  /**
   * Gets a MIME type for a filename.
   *
   * @param string $filename   Filename with extension.
   * @param bool   $useDefault True if the default MIME type should be used when the extension is unknow.
   *
   * @return string|boolean Returns the MIME type or False if it's unknow and useDefault is False.
   */
  public function MIMETypeFromFilename(string $filename, bool $useDefault = true) {
    return $this->MIMETypeFromExtension(pathinfo($filename, PATHINFO_EXTENSION), $useDefault);
  }

  /**
   * Formats a DateTime object to the HTTP protocol format, also converts the timezone to GMT as
   * needed by the protocol. The original object is not modified.
   *
   * @param \DateTime $date A DateTime object.
   *
   * @return string
   */
  public function formatDateTime(\DateTime $date) : string {
    $gmtDate = clone $date;
    $gmtDate->setTimezone(new \DateTimeZone('UTC'));
    return $gmtDate->format(self::HTTP_DATE_FORMAT);
  }

  /**
   * Converts a date string in the HTTP protocol format to a DateTime object.
   *
   * @param string $str A date string.
   *
   * @return \DateTime A DateTime object or null on failure.
   */
  public function dateTimeFromString(string $str) : \DateTime {
    $result = \DateTime::createFromFormat(self::HTTP_DATE_FORMAT, $str);
    return ($result === false) ? null : $result;
  }

  /**
   * Gets a hash of a string. Intended to be used for ETags since it uses a fast algorithm and collisions are not
   * much of a problem.
   *
   * @param string $string A string.
   *
   * @return string
   */
  public function getETagHash(string $string) : string {
    return md5($string);
  }

}
