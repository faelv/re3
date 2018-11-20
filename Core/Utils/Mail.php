<?php

/**
 * Mail class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Utils;

/**
 * Class used to send e-mails. You shouldn't rely on this class for sending a large amount of emails as it is not
 * optimized for that. If you want something with more features (SMTP support for example) take a look at PHPMailer.
 *
 * @package Core
 * @see https://github.com/PHPMailer/PHPMailer
 */
class Mail {

  /**
   * Platform specific end of line character sequence (same as PHP_EOL).
   */
  const EOL_AUTO = PHP_EOL;

  /**
   * Carriage return (CR or \r)
   */
  const EOL_CR   = "\r";

  /**
   * Line feed (LF or \n)
   */
  const EOL_LF   = "\n";

  /**
   * Carriage return and line feed (CRLF or \r\n)
   */
  const EOL_CRLF = "\r\n";

  /**
   * Quoted printable encoding
   */
  const ENCODING_QUOTED = 'quoted-printable';

  /**
   * Base64 encoding
   */
  const ENCODING_BASE64 = 'base64';

  /**
   * 8 bit ascii encoding
   */
  const ENCODING_8BIT   = '8bit';

  /**
   * Stores the current end of line character sequence
   * @var string
   */
  protected $eol = self::EOL_LF;

  /**
   * Stores the current encoding
   * @var string
   */
  protected $encoding = self::ENCODING_BASE64;

  /**
   * Stores additional headers in a key => pair format
   * @var array
   */
  protected $headers = [];

  /**
   * Stores the email receivers. Each element has an 'adress' key and a optional 'name' key.
   * @var array
   */
  protected $receivers = [];

  /**
   * Stores the subject
   * @var string
   */
  protected $subject = '';

  /**
   * Stores the message
   * @var string
   */
  protected $message = '';

  /**
   * Stors the from (sender) address
   * @var string
   */
  protected $from = '';

  /**
   * Stores the sender (from) name
   * @var string
   */
  protected $fromName = '';

  /**
   * Stores the message content type
   * @var string
   */
  protected $contentType = 'text/plain';

  /**
   * Stores the message character set
   * @var string
   */
  protected $charset = 'UTF-8';

  /**
   * Constructor.
   *
   * @param string $receiver Receiver address. Multiple addresses can be supplied but they need to be separated with
   *                         commas. The "name <address>" format is accepted.
   * @param string $subject  The subject.
   * @param string $message  The message body.
   * @param string $from     The From (sender) address. The "name <address>" format is accepted. Note that if you don't
   *                         specify a From address, PHP will use the address in the php.ini file.
   *
   * @return void
   */
  public function __construct(
    string $receiver = null, string $subject = null, string $message = null, string $from = null
  ) {
    if (!is_null($receiver)) {
      $this->addReceiver($receiver);
    }
    if (!is_null($subject)) {
      $this->setSubject($subject);
    }
    if (!is_null($message)) {
      $this->setMessage($message);
    }
    if (!is_null($from)) {
      $this->setFrom($from);
    }
  }

  /**
   * Encodes a string in base64 and enclose if in a =?UTF-8?B? prefix.
   *
   * @param string $value A string
   *
   * @return string The encoded string.
   */
  protected function utf8_base64(string $value) : string {
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
  }

  /**
   * Splits the name and address parts from an email address in the "name <address" format.
   *
   * @param string $email   An email address
   * @param string $name    Variable to receive the name part. Will be empty if there are no name.
   * @param string $address Variable to receive the address part. Will be empty if there are no valid address.
   *
   * @return void
   */
  protected function splitEmailNameAdress(string $email, string &$name, string &$address) {
    if (false !== $lt = mb_strpos($email, '<')) {
      $name = mb_substr($email, 0, $lt);
      $this->sanitizeName($name);
    } else {
      $lt = -1;
      $name = '';
    }

    if (false === $gt = mb_strpos($email, '>')) {
      $gt = null;
    } else {
      $gt = $gt - ($lt + 1);
    }

    $address = mb_substr($email, $lt + 1, $gt);
    $this->sanitizeEmail($address);
  }

  /**
   * Sanitizes a string to be used as the name part in an email address.
   *
   * @param string $value The string to sanitize (by reference).
   *
   * @return void
   */
  protected function sanitizeName(string &$value) {
    $value = trim(mb_ereg_replace('([\v<>,.@]|(?=[^ ])\s)', '', $value));
  }

  /**
   * Sanitizes a string to be used as an email address.
   *
   * @param string $value The string to sanitize (by reference).
   *
   * @return void
   */
  protected function sanitizeEmail(string &$value) {
    $value = trim(filter_var($value, FILTER_SANITIZE_EMAIL));
  }

  /**
   * Sanitizes a string to be used as the subject of an email.
   *
   * @param string $value The string to sanitize (by reference).
   *
   * @return void
   */
  protected function sanitizeSubject(string &$value) {
    $value = trim(filter_var($value, FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_STRIP_LOW]));
  }

  /**
   * Sanitizes a string to be used as an email header.
   *
   * @param string $value The string to sanitize (by reference).
   *
   * @return void
   */
  protected function sanitizeHeader(string &$value) {
    $value = trim(filter_var($value, FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_STRIP_LOW]));
  }

  /**
   * Sanitizes a string to be used as the message body of an email.
   *
   * @param string $value The string to sanitize (by reference).
   *
   * @return void
   */
  protected function sanitizeMessage(string &$value) {
    $value = mb_ereg_replace('(\r\n|\r|\n)', $this->eol, $value);
  }

  /**
   * Sets the current End Of Line character sequence.
   *
   * @param string $value The EOL char(s).
   *
   * @return void
   */
  public function setEOL(string $value) {
    $this->eol = $value;
  }

  /**
   * Sets the email encoding.
   *
   * @param string $value One of the ENCODING_* constants.
   *
   * @return void
   */
  public function setEncoding(string $value) {
    $this->encoding = $value;
  }

  /**
   * Sets additional headers.
   *
   * @param string $name  Header name.
   * @param string $value Header value.
   *
   * @return void
   */
  public function setHeader(string $name, string $value) {
    $this->sanitizeHeader($name);
    $this->sanitizeHeader($value);
    $this->headers[$name] = $value;
  }

  /**
   * Sets the email message content type or MIME type.
   *
   * @param string $type    A proper content type identifier. Defaults to "text/plain".
   * @param string $charset The content character set. Defaults to "UTF-8".
   *
   * @return void
   */
  public function setContentType(string $type, string $charset = 'UTF-8') {
    $this->sanitizeHeader($type);
    $this->sanitizeHeader($charset);
    $this->contentType = empty($type) ? 'text/plain' : $type;
    $this->charset = empty($charset) ? 'UTF-8' : $charset;
  }

  /**
   * Sets the From (sender) address. The "name <address>" format is accepted. Note that if you don't
   * specify a From address, PHP will use the address in the php.ini file.
   *
   * @param string $value The address.
   *
   * @return void
   */
  public function setFrom(string $value) {
    $name = '';
    $addr = '';
    $this->splitEmailNameAdress($value, $name, $addr);
    $this->from = $addr;
    $this->fromName = $name;
  }

  /**
   * Sets the subject.
   *
   * @param string $value The subject.
   *
   * @return void
   */
  public function setSubject(string $value) {
    $this->sanitizeSubject($value);
    $this->subject = $value;
  }

  /**
   * Sets the message.
   *
   * @param string $value The message.
   *
   * @return void
   */
  public function setMessage(string $value) {
    $this->sanitizeMessage($value);
    $this->message = $value;
  }

  /**
   * Adds a receiver ("to" address).
   *
   * @param string $address The receiver's email address. The format "name <address>" is accepted althoug it's preferable
   *                        to use the name parameter. Multiple addresses are also supported if they are separated by
   *                        commas althoug it's preferable to call this method multiple times.
   * @param string $name    The receiver's name. Optional. If you supplied the name with the address it will be ignored.
   *
   * @return void
   */
  public function addReceiver(string $address, string $name = null) {
    if (!is_null($name)) {
      $this->sanitizeName($name);
      if (empty($name)) {
        $name = null;
      }
    }

    $addr_list = mb_split(',', $address);
    foreach ($addr_list as $addr_item) {
      $item_name = '';
      $item_addr = '';
      $this->splitEmailNameAdress($addr_item, $item_name, $item_addr);
      if (!empty($item_addr)) {
        $this->receivers[] = [
          'address' => $item_addr,
          'name' => empty($item_name) ? $name : $item_name
        ];
      }
    }
  }

  /**
   * Removes all added receivers.
   *
   * @return void
   */
  public function clearReceivers() {
    $this->receivers = [];
  }

  /**
   * Sends the email.
   *
   * @return bool True if the email was accepted for delivery, False otherwise. Even though returning True this doesn't
   * mean that the email will be delivery, it only means that the mailer software or system accepted the send
   * request.
   */
  public function send() : bool {
    if (count($this->receivers) == 0) {
      return false;
    }

    $to = '';
    foreach ($this->receivers as $receiver) {
      if ($to != '') {
        $to .= ', ';
      }
      if (empty($receiver['name'])) {
        $to .= '<' . $receiver['address'] . '>';
      } else {
        $to .= $this->utf8_base64($receiver['name']) . ' <' . $receiver['address'] . '>';
      }
    }

    $subject = $this->utf8_base64($this->subject);

    switch ($this->encoding) {
      case self::ENCODING_QUOTED:
        $message = quoted_printable_encode($this->message);
        break;
      case self::ENCODING_8BIT:
        $message = wordwrap($this->message, 75, $this->eol, false);
        break;
      case self::ENCODING_BASE64:
        $message = wordwrap(base64_encode($this->message), 75, $this->eol, true);
        break;
      default:
        $message = $this->message;
    }

    $headers = array_merge(
      $this->headers, [
        'MIME-Version' => '1.0',
        'Content-Type' => $this->contentType . '; charset=' . $this->charset,
        'Content-Transfer-Encoding' => $this->encoding,
        'Date' => date(DATE_RFC822)
      ]
    );

    $message_id = uniqid(crc32($message). '.', true);
    if (isset($_SERVER['SERVER_NAME'])) {
      $headers['Message-Id'] = '<' . $message_id . '@' . $_SERVER['SERVER_NAME'] . '>';
    } elseif (!empty($this->from)) {
      $headers['Message-Id'] = '<' . $message_id . mb_substr($this->from, (int)mb_strpos($this->from, '@')) . '>';
    }

    if (!empty($this->from)) {
      $from = empty($this->fromName) ? '<' . $this->from . '>' : $this->utf8_base64($this->fromName) . ' <' . $this->from . '>';
      $headers['From'] = $from;
      $headers['Reply-To'] = $from;
      $headers['Return-Path'] = $from;
      $headers['Sender'] = $from;
    }

    $result = mail($to, $subject, $message, implode("\r\n", $headers));
    return (bool)$result;
  }

}
