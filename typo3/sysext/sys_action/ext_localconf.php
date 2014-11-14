<?php
if (TYPO3_MODE === 'BE') {
	$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][] = 'TYPO3\\CMS\\SysAction\\Backend\\ToolbarItems\\ActionToolbarItem';
}