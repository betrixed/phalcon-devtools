<?php

/* 
 * Used for common environment features of this module.
 * @author Michael Rynn
 */

namespace ModTools\Controllers;

class BaseController extends \Phalcon\Mvc\Controller\Base {
    protected $mod;
    
    public function initialize() {
        parent::initialize();
        $this->mod = $this->di->get('ctx')->activeModule;
        $this->view->setVar('url_modfix' , $this->getModFix());
    }
    
    public function getModFix() {
        return URL_MODFIX;
    }
    /*
     * Allow for a common URL environment
     */
    public function redirect($path) {
        $prefix = $this->getModFix();
        $this->response->redirect($prefix . $path);
    } 
}