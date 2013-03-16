<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['includeStaticTypoScriptSources'][] =
	'TYPO3\\CMS\\Statictemplates\\StaticTemplatesHook->includeStaticTypoScriptSources';

// Register GMENU_LAYERS, GMENU_FOLDOUT and TMENU_LAYERS menu objects
/** @var $menuContentObjectFactory \TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory */
$menuContentObjectFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
	'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\MenuContentObjectFactory'
);
$menuContentObjectFactory->registerMenuType(
	'GMENU_LAYERS',
	'TYPO3\\CMS\\Statictemplates\\ContentObject\\Menu\\GraphicalMenuLayersContentObject'
);
$menuContentObjectFactory->registerMenuType(
	'GMENU_FOLDOUT',
	'TYPO3\\CMS\\Statictemplates\\ContentObject\\Menu\\GraphicalMenuFoldoutContentObject'
);
$menuContentObjectFactory->registerMenuType(
	'TMENU_LAYERS',
	'TYPO3\\CMS\\Statictemplates\\ContentObject\\Menu\\TextMenuLayersContentObject'
);
?>