<?php

/**
 * SnippetDataSource class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace TemplateEngine\DataSources;

use TemplateEngine\DataSources\DataSource;

/**
 * Data source class that uses TemplateSnippets.
 *
 * @package TemplateEngine
 */
class SnippetDataSource extends DataSource {

  /**
   * Stores the internal template snippet object
   * @var \TemplateEngine\Main\TemplateSnippet
   */
  protected $snippet = null;

  /**
   * Constructor.
   *
   * @param \TemplateEngine\Main\TemplateSnippet $snippet A template snippet.
   */
  public function __construct(\TemplateEngine\Main\TemplateSnippet $snippet = null) {
    $this->setSnippet($snippet);
  }

  /**
   * Sets the template snippet.
   *
   * @param \TemplateEngine\Main\TemplateSnippet $snippet The template snippet.
   *
   * @return void
   */
  public function setSnippet(\TemplateEngine\Main\TemplateSnippet $snippet) {
    $this->snippet = $snippet;
  }

  /**
   * Returns the current template snippet.
   *
   * @return \TemplateEngine\Main\TemplateSnippet The current template snippet.
   */
  public function getSnippet() : \TemplateEngine\Main\TemplateSnippet {
    return $this->snippet;
  }

  /**
   * Returns a string representation of the data of this source.
   *
   * @return string
   */
  public function __toString() : string {
    if (is_null($this->snippet)) {
      return 'NULL';
    }
    return $this->snippet->build();
  }

}
