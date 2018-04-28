<?php

/**
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
$routeData = [
    '/' => [
        'controller' => 'index',
        'actions' => [
            'index' => ['/', ['GET']]
        ]
    ],
    'info' => [
        'controller' => 'system_info',
        'actions' =>[
            'index' => ['/',['GET']]
        ]
    ],
    'migrations' => [
        'actions' => [
            'index' => ['list', ['GET']],
            'generate' => ['', ['GET', 'POST']],
            'run' => ['', ['GET', 'POST']]
        ]
    ],
    'models' => [
        'actions' => [
            'index' => ['list', ['GET']],
            'edit' => ['edit/{file:[\w\d_.~%-]+}', ['GET'], ['file' => 1]],
            'view' => ['view/{file:[\w\d_.~%-]+}', ['GET'], ['file' => 1]],
            'update' => ['', ['POST', 'PUT']],
            'generate' => ['', ['GET', 'POST']],
        ]
    ],
    'controllers' => [
        'actions' => [
            'index' => ['list', ['GET']],
            'edit' => ['edit/{file:[\w\d_.~%-]+}', ['GET'], ['file' => 1]],
            'view' => ['view/{file:[\w\d_.~%-]+}', ['GET'], ['file' => 1]],
            'update' => ['', ['POST', 'PUT']],
            'generate' => ['', ['GET', 'POST']],
        ]
    ],

];
