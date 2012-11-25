<?php
/**
 * MGR Connector
 * 
 * @package gtpbuilder
 * @copyright Alan Pich 2012
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.core.php';
require_once MODX_CORE_PATH.'config/'.MODX_CONFIG_KEY.'.inc.php';
require_once MODX_CONNECTORS_PATH.'index.php';

// Start up the GridEditor service
$path = $modx->getOption('core_path').'components/gtpbuilder/';
require $path.'gtpbuilder.class.php';
 
// Load up some lexiconzzzz
$modx->lexicon->load('gtpbuilder:default');
 
// Handle request 
$modx->request->handleRequest(array(
    'processors_path' => $path.'processors/',
    'location' => '',
));