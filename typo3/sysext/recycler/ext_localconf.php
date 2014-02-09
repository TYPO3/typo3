<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
$TYPO3_CONF_VARS['BE']['AJAX']['RecyclerAjaxController::init'] = 'TYPO3\\CMS\\Recycler\\Controller\\RecyclerAjaxController->init';
