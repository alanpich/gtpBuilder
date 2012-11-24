<?php
/**
 * @package grideditor
 * @author Alan Pich
 */
header('Content-type: text/plain');
$tstart = explode(' ', microtime());
$tstart = $tstart[1] + $tstart[0];
set_time_limit(0);
 
require_once dirname(__FILE__).'/build.tools.php';

/* define package names */
define('PKG_NAME','GridEditor');
define('PKG_NAME_LOWER','grideditor');
define('PKG_VERSION','1.0');
define('PKG_RELEASE','rc1');
define('PKG_COMMIT',getGitCommitId(dirname(dirname(__FILE__))));

echo "Building from commit #".PKG_COMMIT."\n";
/* define build paths */
$root = '/var/www/modx/grideditor/';
$build = dirname(__FILE__).'/';
$sources = array(
    'root' => $root,
    'build' => $build,
    'data' => $build.'data/',
    'resolvers' => $build.'resolvers/',
    'lexicon' => $root . 'core/components/'.PKG_NAME_LOWER.'/lexicon/',
    'docs' => $root.'core/components/'.PKG_NAME_LOWER.'/docs/',
    'elements' => $root.'core/components/'.PKG_NAME_LOWER.'/elements/',
    'source_assets' => $root.'assets/components/'.PKG_NAME_LOWER,
    'source_core' => $root.'core/components/'.PKG_NAME_LOWER,
);
unset($root);
require_once $sources['root'] . 'config.core.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
 
// Set up a MODx Instance ======================================================
$modx= new modX();
$modx->initialize('mgr');
echo '<pre>'; /* used for nice formatting of log messages */
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO');

// Start Building the transport package ========================================
$modx->loadClass('transport.modPackageBuilder','',false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage(PKG_NAME_LOWER,PKG_VERSION,PKG_RELEASE);
$builder->registerNamespace(PKG_NAME_LOWER,false,true,'{core_path}components/'.PKG_NAME_LOWER.'/');
 
// Create a Category for neatness ==============================================
$category= $modx->newObject('modCategory');
$category->set('id',1);
$category->set('category',PKG_NAME);

// Add in demo config chunk ====================================================
$modx->log(modX::LOG_LEVEL_INFO,'Packaging in demo config chunk..');
$chunks = array($modx->newObject('modChunk',array(
        'name' => 'grideditor.config.demo',
        'description' => 'Demo configuration file for GridEditor',
        'snippet' => getSnippetContent($sources['elements'].'chunks/grideditor.config.demo.php')
    )));
$category->addMany($chunks);

// Create Vehicle & add to package =============================================
$attr = array(
    xPDOTransport::UNIQUE_KEY => 'category',
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::RELATED_OBJECTS => true,
    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
        'Chunks' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
        ),
    ),
);
$vehicle = $builder->createVehicle($category,$attr);

// Add in file resolvers =======================================================
$modx->log(modX::LOG_LEVEL_INFO,'Adding file resolvers to package...');
$vehicle->resolve('file',array(
    'source' => $sources['source_assets'],
    'target' => "return MODX_ASSETS_PATH . 'components/';",
));
$vehicle->resolve('file',array(
    'source' => $sources['source_core'],
    'target' => "return MODX_CORE_PATH . 'components/';",
));
$builder->putVehicle($vehicle);


// Add Action and Menu Item ====================================================
$modx->log(modX::LOG_LEVEL_INFO,'Packaging in menu...');
$menu = include $sources['data'].'transport.menu.php';
if (empty($menu)) $modx->log(modX::LOG_LEVEL_ERROR,'Could not package in menu.');
$vehicle= $builder->createVehicle($menu,array (
    xPDOTransport::PRESERVE_KEYS => true,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::UNIQUE_KEY => 'text',
    xPDOTransport::RELATED_OBJECTS => true,
    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
        'Action' => array (
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => array ('namespace','controller'),
        ),
    ),
));
$builder->putVehicle($vehicle);
unset($vehicle,$menu);



// Add documentation ===========================================================
$modx->log(modX::LOG_LEVEL_INFO,'Adding documentation...');
$builder->setPackageAttributes(array(
    'license' => file_get_contents($sources['docs'] . 'license.txt'),
    'readme' => getReadmeFile($sources['docs'] . 'readme.tpl'),
    'changelog' => file_get_contents($sources['docs'] . 'changelog.txt')
));

// Zip up the package ==========================================================
$modx->log(modX::LOG_LEVEL_INFO,'Packing up transport package zip...');
$builder->pack();
 
$tend= explode(" ", microtime());
$tend= $tend[1] + $tend[0];
$totalTime= sprintf("%2.4f s",($tend - $tstart));
$modx->log(modX::LOG_LEVEL_INFO,"\n<br />Package Built.<br />\nExecution time: {$totalTime}\n");
exit ();