<?php

/** 
 * Working with convention that Module is in root of module namespace and directory
 * @author Michael Rynn
 */
namespace ModTools;

use Phalcon\DiInterface;
use Phalcon\Mvc\ModuleDefinitionInterface;

/**
 * 
 */
class Module implements ModuleDefinitionInterface {

    /**
     * Register a specific autoloader for the module
     */
    public function registerAutoloaders(\Phalcon\DiInterface $di = null) {
        $ctx = $di->get('ctx');
        $moduleName = $ctx->getName();
        //$mod = $di->get('modules')->app;
        //\Pcan\Config\mod_LoaderService($di, [$mod->namespace => $mod->modDir]);
    }

    /**
     * Register specific services for the module
     */
    public function registerServices(DiInterface $di) {
        $ctx = $di->get('ctx');
        $moduleName = $ctx->getName();
    }

}

