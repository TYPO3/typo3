<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
		'name' => 'TYPO3\\CMS\\ExtraPageCmOptions\\ExtraPageContextMenuOptions',
	);
}
