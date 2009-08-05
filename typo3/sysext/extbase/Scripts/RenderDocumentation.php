<?php

// Temporary script to render documentation with PHP_UML.

// The path to PHP_UML has to be adjusted by hand right now.
require_once('/Users/sebastian/web-data/flow3/Packages/Application/DocTools/Resources/Private/PHP/PHP_UML/UML.php');
$renderer = new PHP_UML();
$renderer->deploymentView = FALSE;
$renderer->structureFromDocblocks = TRUE;
$renderer->completeAPI = FALSE;
$renderer->setInput('../Classes');
$renderer->parse('Extbase');
$renderer->generateXMI(2.1, 'utf-8');
$renderer->export('html', '../Documentation/API/');
?>