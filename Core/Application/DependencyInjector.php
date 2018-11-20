<?php

/**
 * DependencyInjector Class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Application;

/**
 * Class responsable for automatically setting object properties
 *
 * @package Core
 */
class DependencyInjector {

  /**
   * Stores information about dependency sources
   * @var array
   */
  protected $sources = [];

  /**
   * Stores information about dependency sets of property => source pairs.
   * @var array
   */
  protected $sets = [];

  /**
   * Stores class names aliases
   * @var array
   */
  protected $aliases = [];

  /**
   * A source with a static value, generally a literal.
   */
  const SOURCE_STATIC = 0;

  /**
   * A source with a value that comes from a callable, intended for closures. A reference to the dependency injector
   * will passed as the only parameter.
   */
  const SOURCE_CALLABLE = 1;

  /**
   * A source with a value that will be a instance of a class. A reference to the dependency injector will be passed
   * to the constructor.
   */
  const SOURCE_CLASS = 2;

  /**
   * Gets and returns a value from a source
   *
   * @param string $sourceId The source id
   * @return mixed The source value
   */
  public function getSourceValue(string $sourceId) {
    if (!isset($this->sources[$sourceId])) {
      return null;
    }
    $source = $this->sources[$sourceId];
    switch ($source['type']) {
      case self::SOURCE_STATIC:
        if (!$source['set']) {
          $source['value'] = $source['source'];
          if (is_object($source['value'])) {
            $this->injectInto($source['value']);
          }
        }
        break;
      case self::SOURCE_CALLABLE:
        if (!$source['set'] || !$source['shared']) {
          if (is_callable($source['source'], true)) {
            $source['value'] = $source['source']($this);
            if (is_object($source['value'])) {
              $this->injectInto($source['value']);
            }
          }
        }
        break;
      case self::SOURCE_CLASS:
        if (!$source['set'] || !$source['shared']) {
          if (class_exists($source['source'], true)) {
            $source['value'] = new $source['source']();
            if (is_object($source['value'])) {
              $this->injectInto($source['value']);
            }
          }
        }
        break;
      default:
        return null;
    }
    $source['set'] = true;
    return $source['value'];
  }

  /**
   * Adds a new source of injection.
   *
   * @param string $id     The source id
   * @param mixed  $source Anything accepted as source, depending on it's type.
   * @param int    $type   One of the SOURCE_* constants.
   * @param bool   $shared If a new value should be obtained every time or just one time and used for everyone.
   *
   * @return void
   */
  public function addInjectionSource(string $id, $source, int $type = self::SOURCE_STATIC, bool $shared = false) {
    $this->sources[$id] = [
      'source' => $source,
      'type' => $type,
      'shared' => $shared,
      'value' => null,
      'set' => false
    ];
  }

  /**
   * Adds a new injection set.
   *
   * @param string $id    The set id.
   * @param array  $pairs An array containing pairs of property => source.
   *
   * @return void
   */
  public function addInjectionSet(string $id, array $pairs) {
    $id = trim($id, '\\');
    if (!isset($this->sets[$id])) {
      $this->sets[$id] = $pairs;
    } else {
      $this->sets[$id] += $pairs;
    }
  }

  /**
   * Adds a new alias of a class. Used when more than one class uses the same set of source to property attribuitions.
   * Instead of duplicating the set for every class, you use an alias instead of a class name and "point" that alias
   * to whichever classes you want.
   * TODO: Allow an array of classes
   *
   * @param string $class The class name
   * @param string $alias The alias
   *
   * @return void
   */
  public function addClassAlias(string $class, string $alias) {
    $class = trim($class, '\\');
    $this->aliases[$class] = $alias;
  }

  /**
   * Automatically assigns (injects) values into an object's properties. After all properties have been injected, if the
   * object have a method called __injected, it will be invoked.
   *
   * @param object $object The object whose properties will be auto assigned.
   *
   * @return void
   */
  public function injectInto($object) {
    if (!is_object($object)) {
      return;
    }

    $class = get_class($object);
    do {
      $setIds = [];
      if (isset($this->sets[$class])) {
        $setIds[] = $class;
      }
      if (isset($this->aliases[$class])) {
        $setIds[] = $this->aliases[$class];
      }

      foreach ($setIds as $setId) {
        foreach ($this->sets[$setId] as $property => $sourceId) {
          $object->$property = $this->getSourceValue($sourceId);
        }
      }
    } while (false !== $class = get_parent_class($class));

    if (is_callable([$object, '__injected'])) {
      $object->__injected();
    }
  }
}
