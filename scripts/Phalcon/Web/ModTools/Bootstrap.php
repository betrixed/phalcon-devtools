<?php

/**
 * WebTools is  forked into ModTools to allow a Module context approach
 * ModTools\Bootstrap differs in a few ways from the Phalcon\Bootstrap.
 * It isn't yet any better in functionality, so far just an opportunity to dig into the code.
 * In particular - AnnotationRouter is abandoned, I don't want to make it work,
 * It doesn't seem ready yet for Module URL syntax. 
 * Replaced with RoutesUnpack class and routes.php configuration.
 * A few services get initialized before bootstrap.  I found a lot of cascading change, and 
 * needed to modify everything in some way.
 */

namespace ModTools;

use Phalcon\Mvc\Application as MvcApplication;
use Phalcon\Mvc\Router;
use Phalcon\Error\ErrorHandler;
use Mod\RoutesUnpack;

/**
 * WebTools for Phalcon/devtools
 * @method mixed getShared($name, $parameters=null)
 * @method mixed get($name, $parameters=null)
 * @package Phalcon\Web\Module
 */
class Bootstrap {

    use Configurable, Initializable;

    /**
     * Application instance.
     * @var \Phalcon\Application
     */
    protected $app;
    protected $moduleName;

    /**
     * The services container.
     * @var DiInterface
     */
    protected $di;

    /**
     * The path to the Phalcon Developers Tools.
     * @var string
     */
    protected $ptoolsPath = '';

    /**
     * The allowed IP for access.
     * @var string
     */
    protected $ptoolsIp = '';

    /**
     * The path where the project was created.
     * @var string
     */
    protected $basePath = '';

    /**
     * The DevTools templates path.
     * @var string
     */
    protected $templatesPath = '';

    /**
     * The current hostname.
     * @var string
     */
    protected $hostName = 'Unknown';

    /**
     * The current application mode.
     * @var string
     */
    protected $mode = 'web';

    /**
     * Configurable parameters
     * @var array
     */
    protected $configurable = [
        'ptools_path',
        'ptools_ip',
        'base_path',
        'host_name',
        'templates_path',
    ];

    /**
     * Parameters that can be set using constants
     * @var array
     */
    protected $defines = [
        'PTOOLSPATH',
        'PTOOLS_IP',
        'BASE_PATH',
        'HOSTNAME',
        'TEMPLATE_PATH',
    ];
    protected $loaders = [
        'web' => [
            'eventsManager',
            'config',
            'logger',
            'cache',
            'volt',
            'view',
            'annotations',
            'url',
            'assets',
            'session',
            'flash',
            'database',
            'accessManager',
            'registry',
            'utils',
            'ui',
        ],
        'cli' => [
        // @todo
        ],
    ];

    /**
     * Initialize the Tag Service.
     */
    protected function initTag() {
        $moduleName = $this->moduleName;
        $this->di->setShared(
                'tag', function () use($moduleName) {
            $tag = new Helper\ModTag;
            $tag::$moduleName = $moduleName;
            $tag->setDocType(\Phalcon\Tag::HTML5);
            $tag->setTitleSeparator(' :: ');
            $tag->setTitle('Phalcon ModTools');

            return $tag;
        }
        );
    }

    /**
     * Bootstrap constructor.
     *
     * @param array|\Traversable $parameters
     */
    public function __construct($di, $parameters = []) {
        $this->di = $di;
        $this->defines = array_combine($this->defines, $this->configurable);

        $this->initFromConstants();
        $this->setParameters($parameters);

        $this->app = new MvcApplication($di);
        $ptoolsPath = $this->ptoolsPath;

        $ctx = $this->di->get('ctx');
        $this->moduleName = $ctx->getName();
        $moduleConfig = $ctx->activeModule;
        //$registerArray = $ctx->getRegisterArray();
        
        $this->app->registerModules(
                [ $moduleConfig->alias => function() {
                    // do nothing function
                }]);

        (new ErrorHandler)->register();

        foreach ($this->loaders[$this->mode] as $service) {
            $serviceName = ucfirst($service);
            $this->{'init' . $serviceName}();
        }
        $this->initDispatcher();
        //$this->initRouter();
        $ctx->routerService();
        
        $this->initTag(); // override webtools.php?_url=/
        $this->app->setEventsManager($this->di->getShared('eventsManager'));

        $this->di->setShared('application', $this->app);
        $this->app->setDI($this->di);

        \Phalcon\Di::setDefault($this->di);
    }

    /**
     * Runs the Application.
     *
     * @return \Phalcon\Application|string
     */
    public function run() {
        if (PHP_SAPI == 'cli') {
            set_time_limit(0);
        }

        if (ENV_TESTING === APPLICATION_ENV) {
            return $this->app;
        }

        return $this->getOutput();
    }

    /**
     * Get application output.
     *
     * @return string
     */
    public function getOutput() {
        return $this->app->handle()->getContent();
    }

    /**
     * Sets the path to the Phalcon Developers Tools.
     *
     * @param string $path
     *
     * @return $this
     */
    public function setPtoolsPath($path) {
        $this->ptoolsPath = rtrim($path, '\\/');

        return $this;
    }

    /**
     * Gets the path to the Phalcon Developers Tools.
     *
     * @return string
     */
    public function getPtoolsPath() {
        return $this->ptoolsPath;
    }

    /**
     * Sets the allowed IP for access.
     *
     * @param string $ip
     *
     * @return $this
     */
    public function setPtoolsIp($ip) {
        $this->ptoolsIp = trim($ip);

        return $this;
    }

    /**
     * Gets the allowed IP for access.
     *
     * @return string
     */
    public function getPtoolsIp() {
        return $this->ptoolsIp;
    }

    /**
     * Sets the path where the project was created.
     *
     * @param string $path
     *
     * @return $this
     */
    public function setBasePath($path) {
        $this->basePath = rtrim($path, '\\/');

        return $this;
    }

    /**
     * Gets the path where the project was created.
     *
     * @return string
     */
    public function getBasePath() {
        return $this->basePath;
    }

    /**
     * Sets the DevTools templates path.
     *
     * @param string $path
     *
     * @return $this
     */
    public function setTemplatesPath($path) {
        $this->templatesPath = rtrim($path, '\\/');

        return $this;
    }

    /**
     * Gets the DevTools templates path.
     *
     * @return string
     */
    public function getTemplatesPath() {
        return $this->templatesPath;
    }

    /**
     * Sets the current application mode.
     *
     * @param string $mode
     *
     * @return $this
     */
    public function setMode($mode) {
        $mode = strtolower(trim($mode));

        if (isset($this->loaders[$mode])) {
            $mode = 'web';
        }

        $this->mode = $mode;

        return $this;
    }

    /**
     * Gets the current application mode.
     *
     * @return string
     */
    public function getMode() {
        return $this->mode;
    }

    /**
     * Sets the current hostname.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setHostName($name) {
        $this->hostName = trim($name);

        return $this;
    }

    /**
     * Gets the current application mode.
     *
     * @return string
     */
    public function getHostName() {
        return $this->hostName;
    }

}
