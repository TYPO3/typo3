<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

tx_form_Common::getInstance()
	->initializeFormObjects()
	->initializePageTsConfig();
?>