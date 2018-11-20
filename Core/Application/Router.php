<?php

/**
 * Router class
 *
 * @author faelv <rafael_alt_dev@outlook.com>
 * @license ISC License
 * @see https://github.com/faelv/re3
 */

namespace Core\Application;

/**
 * Responsable for routing (mapping) request URIs to Resources
 *
 * @package Core
 */
class Router {

  /**
   * Stores the routes and their corresponding resource classes
   * @var array
   */
  protected $routes = [];

  /**
   * Stores the default resource class name for invalid routes.
   * @var string
   */
  protected $invalidRouteResource = null;

  /**
   * Stores a request URI prefix that will be ignored by the router.
   * @var string
   */
  protected $ignoredURIPrefix = '';

  /**
   * Checks if a given request uri matches any of the routes.
   *
   * @param string $route  The Route URI
   * @param string $uri    The Request URI
   * @param array  $params Array reference that will be filled with URI parameters, if any, in case of a match.
   *
   * @return bool True if there was a match.
   */
  protected function routeMatch(string $route, string $uri, array &$params) : bool {
    $routeElements = explode('/', rtrim($route, '/'));
    $uriElements = explode('/', rtrim($uri, '/'));

    $routeCount = count($routeElements);
    if ($routeCount != count($uriElements)) {
      return false;
    }

    for ($i = 0; $i < $routeCount; $i++) {
      $re = $routeElements[$i];
      $ue = $uriElements[$i];
      $reLen = strlen($re);
      if ($reLen > 2 && strpos($re, '{') === 0 && strrpos($re, '}') === ($reLen - 1)) {
        $paramName = substr($re, 1, $reLen - 2);
        $colon = strpos($paramName, ':');
        $regex = ($colon === false) ? false : substr($paramName, $colon + 1);
        if ($colon === false || $regex === false || $regex == '') {
          $params[$paramName] = $ue;
        } else {
          $regex = "/$regex/";
          if (preg_match($regex, $ue)) {
            $paramName = substr($paramName, 0, $colon);
            $params[$paramName] = $ue;
          } else {
            return false;
          }
        }
      } elseif ($re != $ue) {
        return false;
      }
    }
    return true;
  }

  /**
   * Adds routes to the router system. A route is composed of a Request URI and the corresponding Resource Class that
   * responds on that URI. The Request URI is a relative path from the domain, without any query string and without
   * slashes at the beginning or at the end. It doesn't necesserally need to correspond to the actual file and directory
   * tree on the disk.
   *
   * For example:
   * <code>
   * addRoute('path/to/my/resource', 'MyResourceClass');
   * addRoute('foo/bar', 'Foo\\BarClass');
   * </code>
   *
   * Portions of the URI can also be used as parameters by enclosing them in brackets, like this:
   * <code>
   * addRoute('users/profile/{id}', 'Users\\Profile');
   * </code>
   *
   * These parameters will be accessible through the request. Parameters will match anything from the request URI at
   * that position, but you can use regular expressions to make them conditional, just put a regex without enclosing it
   * in slashes after a : (colon), after the paramter name:
   * <code>
   * addRoute('static/{folder:(img|js)}/{file}', 'StaticResource');
   * </code>
   *
   * @param string $uri      Request URI.
   * @param string $resource The corresponding resource class
   *
   * @return void
   */
  public function addRoute(string $uri, string $resource) {
    $this->routes[$uri] = $resource;
  }

  /**
   * Same as calling addRoute multiple times.
   *
   * @param array $routes An array where each element key is the request URI and the value is the resource.
   *
   * @return void
   */
  public function addRoutes(array $routes) {
    foreach ($routes as $uri => $resource) {
      $this->addRoute($uri, $resource);
    }
  }

  /**
   * Sets a default resource to be used when a invalid (not found) route is requested.
   *
   * @param string $resource Resource class
   *
   * @return void
   */
  public function setInvalidRouteResource(string $resource) {
    $this->invalidRouteResource = $resource;
  }

  /**
   * Sets a request URI prefix that will be ignored by the router.
   *
   * @param string $prefix Prefix to ignore
   *
   * @return void
   */
  public function setIgnoredURIPrefix(string $prefix) {
    $this->ignoredURIPrefix = $prefix;
  }

  /**
   * Builds an array with route information.
   *
   * @param string|null $resourceClass The resource class or null for no resource class
   * @param string      $requestURI    The request URI
   * @param array       $uriParams     Array with URI parameters
   * @param string      $routeURI      The route URI
   *
   * @return array The route information
   */
  protected function buildRouteArray(
    $resourceClass, string $requestURI = '', array $uriParams = [], string $routeURI = ''
  ) : array {
    return [
      'route' => $routeURI,
      'request_uri' => $requestURI,
      'uri_params' => $uriParams,
      'resource_class' => $resourceClass
    ];
  }

  /**
   * Returns an array with route information for a specified request URI
   *
   * @param string $requestURI The request URI
   *
   * @return array The route information
   */
  public function getRoute(string $requestURI) : array {
    if (!empty($this->ignoredURIPrefix)) {
      if (strpos($requestURI, $this->ignoredURIPrefix) === 0) {
        $requestURI = substr($requestURI, strlen($this->ignoredURIPrefix));
      }
    }

    if ($requestURI == '') {
      $requestURI = '/';
    }
    $uriParams = [];
    foreach ($this->routes as $routeURI => $resourceClass) {
      if ($this->routeMatch($routeURI, $requestURI, $uriParams)) {
        return $this->buildRouteArray($resourceClass, $requestURI, $uriParams, $routeURI);
      }
    }
    return $this->buildRouteArray($this->invalidRouteResource, $requestURI);
  }
}
