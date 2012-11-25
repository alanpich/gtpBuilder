<?php
$action= $modx->newObject('modAction');
$action->fromArray(array(
    'id' => 1,
    'namespace' => PKG_NAME_LOWER,
    'parent' => 0,
    'controller' => 'controller',
    'haslayout' => true,
    'lang_topics' => PKG_NAME_LOWER.':default',
    'assets' => '',
),'',true,true);
 
$menu= $modx->newObject('modMenu');
$menu->fromArray(array(
    'text' => 'Build from GitHub',
    'parent' => 'components',
    'menuindex' => 0,
    'handler' => '',
),'',true,true);
$menu->addOne($action);
unset($menus);
 
return $menu;