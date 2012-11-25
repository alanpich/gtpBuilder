<?php
$path = $modx->getOption('core_path').'components/gtpbuilder/';
if(!class_exists('gtpBuilder')){ require $path.'gtpbuilder.class.php'; };

$owner = 'alanpich';
$repo = 'grideditor';
$branch = 'master';
gtpBuilder::buildFromGithub($owner, $repo, $branch);