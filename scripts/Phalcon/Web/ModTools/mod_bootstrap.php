<?php

/*
 * @author Michael Rynn
 * Experimental Fork to incorporate ModTools into website module framework Pcan
 * This Module.php substitutes for WebTools, without the webtools.php install requirement
 */

namespace ModTools;

/*
  +------------------------------------------------------------------------+
  | Phalcon Developer Tools                                                |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2016 Phalcon Team (https://www.phalconphp.com)      |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file LICENSE.txt.                             |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  |          Serghei Iakovlev <serghei@phalconphp.com>                     |
  +------------------------------------------------------------------------+
 */

use Phalcon\Di\FactoryDefault;
use Phalcon\Error\ErrorHandler;
use Phalcon\Mvc\Application as MvcApplication;
Use Phalcon\Mvc\Router;
Use Phalcon\Mvc\Router\Group as RouterGroup;


// Pcan\ModuleContext is still active
$moduleConfig = $ctx->activeModule;

defined('PTOOLSPATH') || define('PTOOLSPATH', $moduleConfig->PTOOLSPATH);
defined('BASE_PATH') || define('BASE_PATH', $moduleConfig->BASE_PATH);
defined('PTOOLS_IP') || define('PTOOLS_IP', '127.0.0');

/**
 * @const ENV_PRODUCTION Application production stage.
 */
defined('ENV_PRODUCTION') || define('ENV_PRODUCTION', 'production');

/**
 * @const ENV_STAGING Application staging stage.
 */
defined('ENV_STAGING') || define('ENV_STAGING', 'staging');

/**
 * @const ENV_DEVELOPMENT Application development stage.
 */
defined('ENV_DEVELOPMENT') || define('ENV_DEVELOPMENT', 'development');

/**
 * @const ENV_TESTING Application test stage.
 */
defined('ENV_TESTING') || define('ENV_TESTING', 'testing');

/**
 * @const APPLICATION_ENV Current application stage.
 */
defined('APPLICATION_ENV') || define('APPLICATION_ENV', getenv('APPLICATION_ENV') ?: ENV_DEVELOPMENT);

// register ModTools\Controllers, and \Phalcon scripts namespace, but not
require PTOOLSPATH . '/bootstrap/autoload.php';

// Register our stuff
defined('MODTOOLS_DIR') || define('MODTOOLS_DIR',  PTOOLSPATH . '/scripts/Phalcon/Web/ModTools/');

// Define for template URLs could be module  name or could be '/webtools.php?_url=/' or just '/'
defined('URL_MODFIX') || define('URL_MODFIX' , '/' . $moduleConfig->name . '/');
// Finally, 
(new \Phalcon\Loader)->registerNamespaces([
        'ModTools' => MODTOOLS_DIR,
    ])
    ->register();

$bootstrap = new Bootstrap(
        $ctx->getDI(), [
    'ptools_path' => PTOOLSPATH,
    'ptools_ip' => PTOOLS_IP,
    'base_path' => BASE_PATH,
        ]);

if (APPLICATION_ENV === ENV_TESTING) {
    return $bootstrap->run();
} else {
    echo $bootstrap->run();
}