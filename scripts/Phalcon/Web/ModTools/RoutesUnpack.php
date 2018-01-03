<?php

namespace ModTools;

use Phalcon\Mvc\Router;
use Phalcon\Mvc\Router\Group as RouterGroup;

/**
 * Michael Rynn
 * Unpack a dense php array format for a modules routes into \Phalcon\Mvc\Router\
 * 
 * 
    /**
     * Add controller alias keyed array of actions using a Router\Group. 
     * Specified with a condensed style of array representation.
     * This is an encoding that tries to minimise the number of characters.
     * The handling of '/' in the pattern fragment is counter-intuitive.
     * A leading '/' is prefixed to all none-empty patterns. 
     * So they are never specified as starting with a '/',  unless the pattern is only '/' itself,
     * in which case the action pattern is empty! 
 ```
 // The URL becomes /{module-alias}    Router uses /index/index
 '/' => [
        'controller' => 'index',
        'actions' => [
            'index' => ['/', ['GET']]
        ]
    ],
 ```
     * An empty action pattern '' implies the default. In this case the action name is also the URL pattern.
 ```
        'generate' => ['', ['GET', 'POST']], // the pattern '' means use 'generate' as the pattern
 ```
     * For instance this example from Module implementation of modtools.
      ```
      // 'index is for the method 'indexAction' of  class SystemsInfoController,
      // prodcuces router URL pattern as /{module}/{controller-alias}, with no terminating / or /action-pattern
      'info' => [
      'controller' => 'system_info',
      'actions' =>[
      'index' => ['/',['GET']]
      ]
      ],
      ```
     * Arguments passed to ModTools\RoutesUnpack
     * '' in pattern means  same as action name.
     * A '/' means empty pattern, otherwise pattern part 
     * will be prefixed with a '/' when router is setup.
     * a '/' .
     * 
     * First level -  controller-alias
     * Second level 
     *          'action' => $pattern
     *          'actions' array of $action => $pattern
     *                      or $action => [ $pattern, optional ($methods, optional($params)) ]
 */
class RoutesUnpack {

    private $moduleName;
    private $isDefaultModule;
    private $router;

    public function __construct($router, $moduleName, $isDefaultModule = false) {
        $this->router = $router;
        $this->moduleName = $moduleName;
        $this->isDefaultModule = $isDefaultModule;
    }

    /**
     * Handle array of 'actions'
     * @param \Phalcon\Mvc\Router\Group $group
     * @param mixed  $data - items are pattern or [pattern, methods[] ] 
     */
    private function addGroup($group, $data) {
        foreach ($data as $action => $pat2) {
            $methods = null;
            $paths = ['action' => $action];
            if (empty($pat2)) {
                $pattern = '/' . $action;
            } else if (is_string($pat2)) {
                if ($pat2 == '/') {
                    $pattern = '';
                } else {
                    $pattern = '/' . $pat2;
                }
            } else if (is_array($pat2) && count($pat2) > 0) {
                $pattern = $pat2[0];
                if (empty($pattern)) {
                    $pattern = '/' . $action;
                } else if ($pattern == '/') {
                    $pattern = '';
                } else {
                    $pattern = '/' . $pattern;
                }
                $methods = count($pat2) > 1 ? $pat2[1] : null;
                if ((count($pat2) > 2) && is_array($pat2[2])) {
                    $paths = array_merge($paths, $pat2[2]);
                }
            }
            $group->add($pattern, $paths, $methods);
        }
    }

    /**
     * Handle simple 'action' data.
     * @param string $prefix
     * @param string $controller
     * @param mixed $data
     */
    private function addAction($prefix, $controller, $data) {
        if ($controller == '/') {
           $paths = [];
        } else {
            $paths = ['controller' => $controller];
        }
        if (is_array($data) && count($data) > 0) {
            $paths['action'] = $data[0];
            $pattern = $prefix . '/' . $data[0];
            $methods = count($data) > 1 ? $data[1] : null;
            if (count($data > 2) && is_array($data[2])) {
                $paths = array_merge($paths, $data[2]);
            }
            $router->add($pattern, $paths, $methods);
        } else if (is_string($data)) {
            $paths['action'] = $data;
            $this->router->add($prefix . '/' . $data, $paths);
        }
    }

    public function addRouteData($routeData) {
        foreach ($routeData as $prefixName => $rvalues) {
// does prefix include module name?
            if ($prefixName == '/') {
                $prefixName = '';
                $controller = 'index';
            }
            else {
                $controller = $prefixName;
                $prefixName = '/' . $prefixName;
            }
            $prefix = $this->isDefaultModule ? $prefixName : '/' . $this->moduleName .  $prefixName;
            if (isset($rvalues['controller'])) {
                $controller = $rvalues['controller'];
            }
            if (isset($rvalue['action'])) {
                $this->addAction($prefix, $controller, $rvalues['action']);
            } else if (isset($rvalues['actions'])) {
                $routes = new RouterGroup([
                    'module' => $this->moduleName,
                    'controller' => $controller]);
                $routes->setPrefix($prefix);
                $this->addGroup($routes, $rvalues['actions']);
                $this->router->mount($routes);
            }
        }
    }

}
