<?php

/**
 * TemplateSnippet class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace TemplateEngine\Main;

use TemplateEngine\DataProviders\BasicDataProvider;
use TemplateEngine\DataProviders\MultiSetDataProvider;
use TemplateEngine\TemplateSources\StringTemplateSource;

/**
 * A template snippet class is used to load a template from a source and apply data from a data provider to it. A
 * template uses tags where data should be placed, a tag looks like this: {{tag_name}}. When the snippet is built all
 * tags that have a corresponding data in the provider will be replaced by that data.
 *
 * @package TemplateEngine
 */
class TemplateSnippet {

  /**
   * Character sequence used to open a tag.
   * @var string
   */
  protected $openTag = '{{';

  /**
   * Character sequence used to close a tag.
   * @var string
   */
  protected $closeTag = '}}';

  /**
   * Source of the template
   * @var \TemplateEngine\TemplateSources\TemplateSource
   */
  protected $templateSource = null;

  /**
   * String containing the template. Obtained from the source.
   * @var string
   */
  protected $templateString = '';

  /**
   * Stores information about tags discovered in the template source.
   * @var type
   */
  protected $templateTags = [];

  /**
   * The template data provider for this snippet.
   * @var \TemplateEngine\DataProviders\DataProvider
   */
  protected $dataProvider = null;

  /**
   * A callable used to format tag data returned by data providers.
   * @var callable
   */
  protected $dataFormatter = null;

  /**
   * A callback like method, to validate, sanitize or modify tags found in the template.
   *
   * @param string $tag      The tag name.
   * @param int    $position Tag start position
   * @param int    $length   Tag length.
   *
   * @return bool Returning True will make the tag being accepted.
   */
  protected function validateTemplateTag(string &$tag, int &$position, int &$length) : bool {
    return true;
  }

  /**
   * Saves information about a template tag.
   *
   * @param string $tag      The tag name.
   * @param int    $position Starting position of tag. Including it's opening character sequence.
   * @param int    $length   Length of the tag. Including both it's opening and ending character sequences.
   *
   * @return void
   */
  protected function storeTemplateTag(string $tag, int $position, int $length) {
    if ($this->validateTemplateTag($tag, $position, $length)) {
      $this->templateTags[] = ['name' => $tag, 'position' => $position, 'length' => $length];
    }
  }

  /**
   * Clear the template and it's tags.
   *
   * @return void
   */
  protected function clearTemplate() {
    $this->templateTags = [];
    $this->templateString = '';
  }

  /**
   * Gets the template from the source.
   *
   * @return void
   */
  protected function loadTemplate() {
    $this->templateString = $this->templateSource->getTemplate();
  }

  /**
   * Parses the template string for tags.
   *
   * @return void
   */
  protected function parseTemplate() {
    $openTagLen = mb_strlen($this->openTag);
    $closeTagLen = mb_strlen($this->closeTag);
    $tagEnd = 0;
    while (false !== $tagStart = mb_strpos($this->templateString, $this->openTag, $tagEnd)) {
      if (false !== $tagEnd = mb_strpos($this->templateString, $this->closeTag, $tagStart)) {
        $this->storeTemplateTag(
          mb_substr($this->templateString, $tagStart + $openTagLen, $tagEnd - ($tagStart + $openTagLen)),
          $tagStart,
          ($tagEnd + $closeTagLen) - $tagStart
        );
      }
    }
  }

  /**
   * Constructor.
   *
   * @param \TemplateEngine\TemplateSources\TemplateSource $templateSource A template source.
   * @param \TemplateEngine\DataProviders\DataProvider     $dataProvider   A template data provider.
   */
  public function __construct(
    \TemplateEngine\TemplateSources\TemplateSource $templateSource = null,
    \TemplateEngine\DataProviders\DataProvider $dataProvider = null
  ) {
    if (!is_null($templateSource)) {
      $this->setTemplateSource($templateSource);
    } else {
      $this->setTemplateSource(new StringTemplateSource());
    }

    if (!is_null($dataProvider)) {
      $this->setDataProvider($dataProvider);
    } else {
      $this->setDataProvider(new BasicDataProvider());
    }
  }

  /**
   * Sets the template source for this snippet.
   *
   * @param \TemplateEngine\TemplateSources\TemplateSource $templateSource A template source.
   *
   * @return void
   */
  public function setTemplateSource(\TemplateEngine\TemplateSources\TemplateSource $templateSource) {
     $this->templateSource = $templateSource;
  }

  /**
   * Returns the current template source of this snippet.
   *
   * @return \TemplateEngine\TemplateSources\TemplateSource
   */
  public function getTemplateSource() : \TemplateEngine\TemplateSources\TemplateSource {
    return $this->templateSource;
  }

  /**
   * Sets the template data provider for this snippet.
   *
   * @param \TemplateEngine\DataProviders\DataProvider $dataProvider A data provider.
   *
   * @return void
   */
  public function setDataProvider(\TemplateEngine\DataProviders\DataProvider $dataProvider) {
    $this->dataProvider = $dataProvider;
  }

  /**
   * Returns the current data provider of this snippet.
   *
   * @return \TemplateEngine\DataProviders\DataProvider
   */
  public function getDataProvider() : \TemplateEngine\DataProviders\DataProvider {
    return $this->dataProvider;
  }

  /**
   * Sets a callable used to format tag data returned by data providers.
   *
   * @param callable $formatter A callable that receives two parameters, the first is the tag name and the second is the
   *                            tag data. It must return a string.
   *                            <code>function(string name, value) : string;</code>
   *
   * @return void
   */
  public function setDataFormatter(callable $formatter) {
    $this->dataFormatter = $formatter;
  }

  /**
   * Builds the snippet (replaces tags with data). Used when the snippet's data provider has just a single data set
   * (i.e. it not extends MultiSetDataProvider).
   *
   * @return string The template with replaced tags.
   */
  protected function buildSingle() : string {
    $result = '';
    $offset = 0;
    foreach ($this->templateTags as $tag) {
      $tagValue = $this->dataProvider->getData($tag['name']);
      if ($tagValue === false) {
        $tagValue = $this->openTag . $tag['name'] . $this->closeTag;
      } elseif (!is_null($this->dataFormatter)) {
        $tagValue = ($this->dataFormatter)($tag['name'], $tagValue);
      }

      $result .= mb_substr($this->templateString, $offset, $tag['position'] - $offset);
      $result .= $tagValue;

      $offset = $tag['position'] + $tag['length'];
    }
    $result .= mb_substr($this->templateString, $offset);
    return $result;
  }

  /**
   * Used to build the snippet when it's data provider has more than one data set (i.e. it extends MultiSetDataProvider).
   * The basic behavior is identical to the buildSingle method.
   *
   * @return string A concatenation of every template built.
   */
  protected function buildMulti() : string {
    $result = '';
    do {
      $result .= $this->buildSingle();
    } while ($this->dataProvider->nextDataSet());
    return $result;
  }

  /**
   * Build the snippet (replaces tags with it's corresponding data).
   * @return string The template with replaced tags.
   */
  public function build() : string {
    $this->clearTemplate();

    if (is_null($this->templateSource)) {
      return '';
    }

    $this->loadTemplate();

    if (is_null($this->dataProvider)) {
      return $this->templateString;
    }

    $this->parseTemplate();

    if ($this->dataProvider instanceof MultiSetDataProvider) {
      return $this->buildMulti();
    } else {
      return $this->buildSingle();
    }
  }

}
