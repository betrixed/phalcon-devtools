<?php

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

namespace ModTools;

use Phalcon\Mvc\View;
use DirectoryIterator;
use Phalcon\Utils\DbUtils;
use Phalcon\Utils\FsUtils;
use Phalcon\Utils\SystemInfo;
use Phalcon\Mvc\View\Engine\Php;
use Phalcon\Logger\Adapter\Stream;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Resources\AssetsResource;
use Phalcon\Elements\Menu\SidebarMenu;
use Phalcon\Mvc\View\NotFoundListener;
use Phalcon\Flash\Direct as FlashDirect;
use Phalcon\Access\Policy\Ip as IpPolicy;
use Phalcon\Flash\Session as FlashSession;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Events\Event;

use Phalcon\Assets\Manager as AssetsManager;
use Phalcon\Access\Manager as AccessManager;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use Phalcon\Scanners\Config as ConfigScanner;
use Phalcon\Session\Adapter\Files as Session;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\File as FileLogger;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use Phalcon\Config as PhConfig;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Config\Exception as ConfigException;
use Phalcon\Cache\Frontend\None as FrontendNone;
use Phalcon\Cache\Backend\Memory as BackendCache;
use Phalcon\Cache\Frontend\Output as FrontOutput;
use Phalcon\Mvc\Router\Annotations as AnnotationsRouter;
use Phalcon\Mvc\View\Engine\Volt\Extension\Php as PhpExt;
use Phalcon\Annotations\Adapter\Memory as AnnotationsMemory;
use Phalcon\Mvc\Dispatcher\ErrorHandler as DispatchErrorHandler;
use Pcan\Config\ModuleContext;

/**
 * \Phalcon\Initializable
 *
 * @property DiInterface $di
 *
 * @package Phalcon
 */
trait Initializable {

    /**
     * Initialize the Application Events Manager.
     */
    protected function initEventsManager() {
        $this->di->setShared(
                'eventsManager', function () {
            $em = new EventsManager;
            $em->enablePriorities(true);

            return $em;
        }
        );
    }

    /**
     * Initialize the Application Config.
     */
    protected function initConfig() {
        $basePath = $this->basePath;
        $this->di->setShared(
                'config', function () use ($basePath) {
            /** @var DiInterface $this */
            $scanner = new ConfigScanner($basePath);
            $config = $scanner->load('config');

            if (ENV_PRODUCTION !== APPLICATION_ENV) {
                $override = $scanner->scan(APPLICATION_ENV);
                if ($override instanceof PhConfig) {
                    $config->merge($override);
                }
            }

            return $config;
        }
        );
    }

    /**
     * Initialize the Logger.
     */
    protected function initLogger() {
        $hostName = $this->hostName;
        $basePath = $this->basePath;

        $this->di->setShared(
                'logger', function () use ($hostName, $basePath) {
            $logLevel = Logger::ERROR;
            if (ENV_DEVELOPMENT === APPLICATION_ENV) {
                $logLevel = Logger::DEBUG;
            }

            $ptoolsPath = $basePath . DS . '.phalcon' . DS;
            if (is_dir($ptoolsPath) && is_writable($ptoolsPath)) {
                $formatter = new LineFormatter("%date% {$hostName} php: [%type%] %message%", 'D j H:i:s');
                $logger = new FileLogger($ptoolsPath . 'devtools.log');
            } else {
                $formatter = new LineFormatter("[devtools@{$hostName}]: [%type%] %message%", 'D j H:i:s');
                $logger = new Stream('php://stderr');
            }

            $logger->setFormatter($formatter);
            $logger->setLogLevel($logLevel);

            return $logger;
        }
        );
    }

    /**
     * Initialize the Cache.
     *
     * The frontend must always be Phalcon\Cache\Frontend\Output and the service 'viewCache'
     * must be registered as always open (not shared) in the services container (DI).
     */
    protected function initCache() {
        $this->di->set(
                'viewCache', function () {
            return new BackendCache(new FrontOutput);
        }
        );

        $this->di->setShared(
                'modelsCache', function () {
            return new BackendCache(new FrontendNone);
        }
        );

        $this->di->setShared(
                'dataCache', function () {
            return new BackendCache(new FrontendNone);
        }
        );
    }

    /**
     * Initialize the Volt Template Engine.
     */
    protected function initVolt() {
        $basePath = $this->basePath;
        $ptoolsPath = $this->ptoolsPath;
        $that = $this;

        $this->di->setShared(
                'volt', function ($view, $di) use ($basePath, $ptoolsPath, $that) {
            /**
             * @var DiInterface $this
             * @var Config $config
             * @var Config $voltConfig
             */
            $volt = new VoltEngine($view, $di);
            $config = $this->getShared('config');

            // So much trouble for a volt cache directory
            // set as 'voltCacheDir'

            $voltConfig = null;
            if ($config->offsetExists('volt')) {
                $voltConfig = $config->get('volt'); // this is the best
            } elseif ($config->offsetExists('view')) {
                $voltConfig = $config->get('view'); // 2nd best
            }
            $voltCacheDir = null;
            if ($voltConfig instanceof PhConfig) {
                $voltCacheDir = $voltConfig->get('cacheDir', '');
            }
            if (empty($voltCacheDir)) {
                $defaultCacheDir = sys_get_temp_dir() . DS . 'phalcon' . DS . 'volt'; // backstop
                if ($config->offsetExists('application')) {
                    $voltCacheDir = $config->application->get('cacheDir', $defaultCacheDir);
                    if (empty($voltCacheDir)) {
                        $voltCacheDir = $defaultCacheDir;
                    }
                }
            }
            // assume that $default is always writeable
            if ($voltCacheDir !== $defaultCacheDir) {
                if (!is_dir($voltCacheDir) || !is_writable($voltCacheDir)) {
                    $this->di->getShared('logger')->warning(
                            'Unable to initialize Volt cache dir: {cache}. Used temp path: {default}', [
                        'cache' => $cacheDir,
                        'default' => $defaultCacheDir
                    ]);
                    $voltCacheDir = $defaultCacheDir;
                    if (!is_dir($voltCacheDir)) {
                        mkdir($voltCacheDir, 0777, true);
                    }
                }
            }

            if (!$voltConfig instanceof PhConfig) {
                $voltConfig = new PhConfig([
                    'compiledExt' => '.php',
                    'separator' => '_',
                    'cacheDir' => $voltCacheDir,
                    'forceCompile' => ENV_DEVELOPMENT === APPLICATION_ENV,
                ]);
            } else {
                $voltConfig->cacheDir = $voltCacheDir; // Set no matter what
            }

            // a function to generate the compile files name. 
            $compiledPath = function ($templatePath) use (
                    $voltConfig,
                    $basePath,
                    $ptoolsPath
                    ) {
                /**
                 * @var DiInterface $this
                 * @var Config $voltConfig
                 */
                if (0 === strpos($templatePath, $basePath)) {
                    $templatePath = substr($templatePath, strlen($basePath));
                } elseif (0 === strpos($templatePath, $ptoolsPath . DS . 'scripts')) {
                    $templatePath = substr($templatePath, strlen($ptoolsPath . DS . 'scripts'));
                }

                $templatePath = trim($templatePath, '\\/');
                $filename = str_replace(['\\', '/'], $voltConfig->get('separator', '_'), $templatePath);
                $filename = basename($filename, '.volt') . $voltConfig->get('compiledExt', '.php');
                return rtrim($voltConfig->cacheDir, '\\/') . DS . $filename;
            };

            $options = [
                'compiledPath' => $voltConfig->get('compiledPath', $compiledPath),
                'compileAlways' => ENV_DEVELOPMENT === APPLICATION_ENV || boolval($voltConfig->get('forceCompile')),
            ];

            $volt->setOptions($options);
            $volt->getCompiler()->addExtension(new PhpExt);

            return $volt;
        }
        );
    }

    /**
     * get volt cache directory
     *
     * @param Config $voltConfig
     *
     * @return string
     */
    protected function getCacheDir(PhConfig $voltConfig) {
        $appCacheDir = $this->di->getShared('config')->path('application.cacheDir');
        $cacheDir = $voltConfig->get('cacheDir', $appCacheDir);
        $defaultCacheDir = sys_get_temp_dir() . DS . 'phalcon' . DS . 'volt';

        if ($cacheDir && is_dir($cacheDir) && is_writable($cacheDir)) {
            return $cacheDir;
        }


        if (!is_dir($defaultCacheDir)) {
            mkdir($defaultCacheDir, 0777, true);
        }

        return $defaultCacheDir;
    }

    /**
     * Initialize the View.
     */
    protected function initView() {
        $this->di->setShared(
                'view', function () {
            /**
             * @var DiInterface $this
             * @var Registry $registry
             */
            $view = new View;
            $registry = $this->getShared('registry');

            $view->registerEngines(
                    [
                        '.volt' => $this->getShared('volt', [$view, $this]),
                        '.phtml' => Php::class
                    ]
            );

            $view->setViewsDir($registry->offsetGet('directories')->webToolsViews . DS)
                    ->setLayoutsDir('layouts' . DS)
                    ->setRenderLevel(View::LEVEL_AFTER_TEMPLATE);

            $em = $this->getShared('eventsManager');
            $em->attach('view', new NotFoundListener);

            $view->setEventsManager($em);

            return $view;
        }
        );
    }

    /**
     * Initialize the Annotations.
     */
    protected function initAnnotations() {
        $this->di->setShared(
                'annotations', function () {
            return new AnnotationsMemory;
        }
        );
    }

    /**
     * Initialize the Router.
     */
    protected function initRouter() {
        $ptoolsPath = $this->ptoolsPath;
        $ctx = $this->di->get('ctx');
        $moduleName = $ctx->getName();
        $this->di->setShared(
                'router', function () use ($moduleName, $ptoolsPath) {
            /** @var DiInterface $this */
            $em = $this->getShared('eventsManager');

            $router = new AnnotationsRouter(false);
            $router->removeExtraSlashes(true);

            // @todo Use Path::normalize()
            $controllersDir = $ptoolsPath . DS . str_replace('/', DS, 'scripts/Phalcon/Web/Tools/Controllers');
            $dir = new DirectoryIterator($controllersDir);

            $resources = [];

            foreach ($dir as $fileInfo) {
                if ($fileInfo->isDot() || false === strpos($fileInfo->getBasename(), 'Controller.php')) {
                    continue;
                }

                $controller = $fileInfo->getBasename('Controller.php');
                $resources[] = $controller;
            }
            if (!empty($moduleName)) {
                foreach ($resources as $controller) {
                    $router->addModuleResource($moduleName, $controller);
                }
            } else {
                foreach ($resources as $controller) {
                    $router->addResource($controller);
                }
            }
            
            
            $router->setEventsManager($em);
            $router->setDefaultAction('index');
            $router->setDefaultController('index');
            $router->setDefaultNamespace('ModTools\Controllers');
            $router->notFound(['controller' => 'error', 'action' => 'route404']);

            return $router;
        }
        );
    }

    /**
     * Initialize the Url service.
     */
    protected function initUrl() {
        $ctx = $this->di->get('ctx');
         // assume not default module name
        $cfg = $ctx->activeModule;
        // dynamic prefix for BaseUri
        $prefix = '';
        //($cfg->isDefaultModule) ? '' : $cfg->name . '/';
        // static links uneffected
        $this->di->setShared(
                'url', function () use ($prefix) {
            /**
             * @var DiInterface $this
             * @var Config $config
             */
            $config = $this->getShared('config');

            $url = new UrlResolver;

            if ($config->get('application', new PhConfig)->offsetExists('baseUri')) {
                $baseUri = $config->get('application', new PhConfig)->get('baseUri');
            } elseif ($config->offsetExists('baseUri')) {
                $baseUri = $config->get('baseUri');
            } else {
                // @todo Log notice here
                $baseUri = '/';
            }

            if ($config->get('application', new PhConfig)->offsetExists('staticUri')) {
                $staticUri = $config->get('application', new PhConfig)->get('staticUri');
            } elseif ($config->offsetExists('staticUri')) {
                $staticUri = $config->get('staticUri');
            } else {
                // @todo Log notice here
                $staticUri = '/';
            }
            
            if (!empty($prefix)) {
                $baseUri .= $prefix;
            }
            // before we go, are we in a module or not - 
            $url->setBaseUri($baseUri);
            $url->setStaticBaseUri($staticUri);

            return $url;
        }
        );
    }



    /**
     * Initialize the Dispatcher.
     */
    protected function initDispatcher() {
        $this->di->setShared(
                'dispatcher', function () {
            /** @var DiInterface $this */
            $em = $this->get('eventsManager');

            $dispatcher = new MvcDispatcher();
            $dispatcher->setDefaultNamespace('ModTools\Controllers');
            
            $em->attach('dispatch:beforeDispatchLoop', 
                    function(Event $event, $dispatcher )  {
                        $action = $dispatcher->getActionName();
                        $controller = $dispatcher->getControllerName();
                        $ctx = $dispatcher->getDi()->get('ctx');
                        $ctx->debug($controller,$action);
                    });
            $em->attach('dispatch', $this->getShared('access'), 1000);
            $em->attach('dispatch:beforeException', new DispatchErrorHandler, 999);

            $dispatcher->setEventsManager($em);

            return $dispatcher;
        }
        );
    }

    /**
     * Initialize the Assets Manager.
     */
    protected function initAssets() {
        $this->di->setShared(
                'assets', function () {
            return new AssetsManager;
        }
        );
    }

    /**
     * Initialize the Session Service.
     */
    protected function initSession() {
        $this->di->setShared(
                'session', function () {
            $session = new Session;
            $session->start();

            return $session;
        }
        );
    }

    /**
     * Initialize the Flash Service.
     */
    protected function initFlash() {
        $this->di->setShared(
                'flash', function () {
            $flash = new FlashDirect(
                    [
                'error' => 'alert alert-danger fade in',
                'success' => 'alert alert-success fade in',
                'notice' => 'alert alert-info fade in',
                'warning' => 'alert alert-warning fade in',
                    ]
            );

            $flash->setAutoescape(false);

            return $flash;
        }
        );

        $this->di->setShared(
                'flashSession', function () {
            $flash = new FlashSession(
                    [
                'error' => 'alert alert-danger fade in',
                'success' => 'alert alert-success fade in',
                'notice' => 'alert alert-info fade in',
                'warning' => 'alert alert-warning fade in',
                    ]
            );


            $flash->setAutoescape(false);

            return $flash;
        }
        );
    }

    /**
     * Initialize the Database connection.
     */
    protected function initDatabase() {
        $this->di->setShared(
                'db', function () {
            /** @var DiInterface $this */
            $em = $this->getShared('eventsManager');

            if ($this->getShared('config')->offsetExists('database')) {
                $config = $this->getShared('config')->get('database')->toArray();
            } else {
                $dbname = sys_get_temp_dir() . DS . 'phalcon.sqlite';
                $this->getShared('logger')->warning(
                        'Unable to initialize "db" service. Used Sqlite adapter at path: {path}', ['path' => $dbname]
                );

                $config = [
                    'adapter' => 'Sqlite',
                    'dbname' => $dbname,
                ];
            }

            $adapter = 'Phalcon\Db\Adapter\Pdo\\' . $config['adapter'];
            unset($config['adapter']);

            /** @var \Phalcon\Db\Adapter\Pdo $connection */
            $connection = new $adapter($config);

            $connection->setEventsManager($em);

            return $connection;
        }
        );
    }

    /**
     * Initialize the Access Manager.
     */
    protected function initAccessManager() {
        $ptoolsIp = $this->ptoolsIp;

        $this->di->setShared(
                'access', function () use ($ptoolsIp) {
            /** @var DiInterface $this */
            $em = $this->getShared('eventsManager');

            $policy = new IpPolicy($ptoolsIp);

            $manager = new AccessManager($policy);
            $manager->setEventsManager($em);

            return $manager;
        }
        );
    }

    /**
     * Initialize the global registry.
     */
    protected function initRegistry() {
        $basePath = $this->basePath;
        $ptoolsPath = $this->ptoolsPath;
        $templatesPath = $this->templatesPath;

        $this->di->setShared(
                'registry', function () use ($basePath, $ptoolsPath, $templatesPath) {
            /**
             * @var DiInterface $this
             * @var Config $config
             * @var FsUtils $fs
             */
            $registry = new \Phalcon\Registry();

            $config = $this->getShared('config');
            $fs = $this->getShared('fs');

            $basePath = $fs->normalize(rtrim($basePath, '\\/'));
            $ptoolsPath = $fs->normalize(rtrim($ptoolsPath, '\\/'));
            $templatesPath = $fs->normalize(rtrim($templatesPath, '\\/'));

            $requiredDirectories = [
                'modelsDir',
                'controllersDir',
                'migrationsDir',
            ];

            $directories = [
                'modelsDir' => null,
                'controllersDir' => null,
                'migrationsDir' => null,
                'basePath' => $basePath,
                'ptoolsPath' => $ptoolsPath,
                'templatesPath' => $templatesPath,
                'webToolsViews' => $fs->normalize($ptoolsPath . '/scripts/Phalcon/Web/ModTools/Views'),
                'resourcesDir' => $fs->normalize($ptoolsPath . '/resources'),
                'elementsDir' => $fs->normalize(MODTOOLS_DIR . '/resources/elements')
            ];

            if (($application = $config->get('application')) instanceof PhConfig) {
                foreach ($requiredDirectories as $name) {
                    if ($possiblePath = $application->get($name)) {
                        if (!$fs->isAbsolute($possiblePath)) {
                            $possiblePath = $basePath . DS . $possiblePath;
                        }

                        $possiblePath = $fs->normalize($possiblePath);
                        if (is_readable($possiblePath) && is_dir($possiblePath)) {
                            $directories[$name] = $possiblePath;
                        }
                    }
                }
            }

            $registry->offsetSet('directories', (object) $directories);

            return $registry;
        }
        );
    }

    /**
     * Initialize utilities.
     */
    protected function initUtils() {
        $this->di->setShared(
                'fs', function () {
            return new FsUtils;
        }
        );

        $this->di->setShared(
                'info', function () {
            return new SystemInfo;
        }
        );

        $this->di->setShared(
                'dbUtils', function () {
            return new DbUtils;
        }
        );

        $this->di->setShared(
                'resource', function () {
            return new AssetsResource;
        }
        );
    }

    /**
     * Initialize User Interface components (mostly HTML elements).
     */
    protected function initUi() {
        $that = $this;

        $this->di->setShared(
                'sidebar', function () use ($that) {
            /**
             * @var Registry $registry
             */
            $registry = $that->di->getShared('registry');
            $menuItems = $registry->offsetGet('directories')->elementsDir . DS . 'sidebar-menu.php';

            /** @noinspection PhpIncludeInspection */
            $menu = new SidebarMenu(include $menuItems);

            $menu->setDI($that->di);

            return $menu;
        }
        );
    }

}
