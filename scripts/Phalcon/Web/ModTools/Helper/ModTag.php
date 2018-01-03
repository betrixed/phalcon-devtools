<?php

namespace ModTools\Helper;

class ModTag extends \Phalcon\Tag {
    static public $moduleName;
    
    /* 
     * Does as linkTo, but linkMod creates a link within the current module
     * by prefix of current module name to relative links.
     * It needs to be as service with the current module name.
     */
    public static function linkMod( $parameters, $text = NULL, $local  = NULL) {
        $params = is_array($parameters) 
                    ? $parameters
                    : [ $parameters, $text, $local];
        $ct = count($params);
        if ($ct > 0) {
            $action = $params[0];
        }
        else if (isset($params['action'])) {
            $action = $params['action'];
            unset($params['action']);
        }
        else {
            $action = '';
        }
        if ($ct > 1) {
            $text = $params[1];
        }
        else if (isset($params['text'])) {
            $text = $params['text'];
            unset($params['text']);
        }
        else {
            $text = '';
        }
        if ($ct > 2) {
            $local = $params[2];
        }
        else if (isset($params['local'])) {
            $local = $params['local'];
            unset($params['local']);
        }
        else {
            $local = true;
        }
        // query is the ? and after part of the URL
        if (isset($params['query'])) {
            $query = $params['query'];
            unset ( $params['query']);
        }
        else {
            $query = null;
        }
        
        if ($local && !empty(self::$moduleName)) {
            $action = self::$moduleName . '/' . $action;
        }
        $url = self::getUrlService();
        $params['href'] = $url->get($action,$query,$local);
        $code = self::renderAttributes('<a', $params);
        $code .= '>' . $text . '</a>';
        return $code;
    }

}

