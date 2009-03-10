<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TYPO3_CONF_VARS['BE']['AJAX']['tx_recycler::controller'] = t3lib_extMgm::extPath($_EXTKEY) . 'classes/controller/class.tx_recycler_controller_ajax.php:tx_recycler_controller_ajax->init';

?>