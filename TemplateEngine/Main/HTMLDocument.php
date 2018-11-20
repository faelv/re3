<?php

/**
 * HTMLDocument class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace TemplateEngine\Main;

use TemplateEngine\Main\TemplateSnippet;

/**
 * A class for generating HTML documents from templates.
 *
 * @package TemplateEngine
 */
class HTMLDocument extends TemplateSnippet {

  /**
   * Store head elements.
   * @var array
   */
  protected $headElements = [];

  /**
   * Stores body elements.
   * @var array
   */
  protected $bodyElements = [];

  /**
   * Returns an array with element information.
   *
   * @param string $tagName    Tag name
   * @param array  $attributes Array of element attributes in a name => value format.
   * @param bool   $hasEndTag  If the element has an end/closing tag.
   * @param string $innerValue The inner value of the element. Ignored if the element doesn't have a end tag.
   *
   * @return array
   */
  protected function buildElementArray(
    string $tagName, array $attributes = null, bool $hasEndTag = false, string $innerValue = null
  ) : array {
    return [
      'tag_name' => $tagName,
      'attributes' => is_array($attributes) ? $attributes : [],
      'has_end_tag' => $hasEndTag,
      'inner_value' => is_string($innerValue) ? $innerValue : ''
    ];
  }

  /**
   * Returns the markup built from the element array.
   *
   * @param array $element Element info array.
   *
   * @return string Markup string.
   */
  protected function buildElementString(array $element) : string {
    $result = "<{$element['tag_name']}";
    foreach ($element['attributes'] as $attrName => $attrValue) {
      if (is_string($attrName) && is_string($attrValue)) {
        $result .= " $attrName=\"$attrValue\"";
      }
    }
    if (!$element['has_end_tag']) {
      $result .= ' />';
    } else {
      $result .= ">{$element['inner_value']}</{$element['tag_name']}>";
    }
    return $result . PHP_EOL;
  }

  /**
   * Loads the template from source and adds header and body elements to it.
   *
   * @return void
   */
  protected function loadTemplate() {
    parent::loadTemplate();

    $offset = 0;
    $tmpTemplateString = '';
    $headEndTag = '</head>';
    $bodyEndTag = '</body>';

    if (count($this->headElements) > 0) {
      if (false !== $headPos = mb_strpos($this->templateString, $headEndTag)) {
        $tmpTemplateString .= mb_substr($this->templateString, $offset, $headPos - $offset);

        foreach ($this->headElements as $elm) {
          $tmpTemplateString .= $this->buildElementString($elm);
        }

        $tmpTemplateString .= $headEndTag;
        $offset += $headPos + mb_strlen($headEndTag);
      }
    }

    if (count($this->bodyElements) > 0) {
      if (false !== $bodyPos = mb_strpos($this->templateString, $bodyEndTag)) {
        $tmpTemplateString .= mb_substr($this->templateString, $offset, $bodyPos - $offset);

        foreach ($this->bodyElements as $elm) {
          $tmpTemplateString .= $this->buildElementString($elm);
        }

        $tmpTemplateString .= $bodyEndTag;
        $offset += $bodyPos + mb_strlen($bodyEndTag);
      }
    }

    if ($offset > 0) {
      $tmpTemplateString .= mb_substr($this->templateString, $offset);
      $this->templateString = $tmpTemplateString;
      $tmpTemplateString = null; //GC hint, sorry if i'm paranoid
    }
  }

  /**
   * Adds an element as a child of the head element, if it is present.
   *
   * @param string $tagName    Tag name
   * @param array  $attributes Array of element attributes in a name => value format.
   * @param bool   $hasEndTag  If the element has an end/closing tag.
   * @param string $innerValue The inner value of the element. Ignored if the element doesn't have a end tag.
   *
   * @return void
   */
  public function addElementToHead(
    string $tagName, array $attributes = null, bool $hasEndTag = false, string $innerValue = null
  ) {
    $this->headElements[] = $this->buildElementArray($tagName, $attributes, $hasEndTag, $innerValue);
  }

  /**
   * Adds an element as a child of the body element, if it is present.
   *
   * @param string $tagName    Tag name
   * @param array  $attributes Array of element attributes in a name => value format.
   * @param bool   $hasEndTag  If the element has an end/closing tag.
   * @param string $innerValue The inner value of the element. Ignored if the element doesn't have a end tag.
   *
   * @return void
   */
  public function addElementToBody(
    string $tagName, array $attributes = null, bool $hasEndTag = false, string $innerValue = null
  ) {
    $this->bodyElements[] = $this->buildElementArray($tagName, $attributes, $hasEndTag, $innerValue);
  }

  /**
   * Adds a meta tag.
   *
   * @param string $name    Tag name
   * @param string $content Tag content
   *
   * @return void
   */
  public function addMeta(string $name, string $content) {
    $this->addElementToHead('meta', ['name' => $name, 'content' => $content]);
  }

  /**
   * Adds an external style sheet file.
   *
   * @param string $href      Style sheet URI/location
   * @param string $media     Target media
   * @param string $integrity Integrity hash
   * @param string $cors      CORS (cross origin) policy
   *
   * @return void
   */
  public function addStyleSheet(string $href, string $media = 'all', string $integrity = null, string $cors = null) {
    $this->addElementToHead(
      'link', [
        'rel' => 'stylesheet',
        'href' => $href,
        'media' => $media,
        'integrity' => $integrity,
        'crossorigin' => $cors
      ]
    );
  }

  /**
   * Adds a style element.
   *
   * @param string $css CSS rules.
   *
   * @return void
   */
  public function addStyle(string $css) {
    $this->addElementToHead('style', null, true, $css);
  }

  /**
   * Adds an external script file.
   *
   * @param string $src       Script URI/location.
   * @param bool   $body      True if the script should be inserted into the body element, or False for the head element.
   * @param string $integrity Integrity hash
   * @param string $cors      CORS (cross origin) policy
   *
   * @return void
   */
  public function addExternalScript(string $src, bool $body = true, string $integrity = null, string $cors = null) {
    if ($body) {
      $this->addElementToBody('script', ['src' => $src, 'integrity' => $integrity, 'crossorigin' => $cors], true);
    } else {
      $this->addElementToHead('script', ['src' => $src, 'integrity' => $integrity, 'crossorigin' => $cors], true);
    }
  }

  /**
   * Adds a script element.
   *
   * @param string $js   Script code.
   * @param bool   $body True if the script should be inserted into the body element, or False for the head element.
   *
   * @return void
   */
  public function addScript(string $js, bool $body = true) {
    if ($body) {
      $this->addElementToBody('script', null, true, $js);
    } else {
      $this->addElementToHead('script', null, true, $js);
    }
  }

}
