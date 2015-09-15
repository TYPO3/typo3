<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
	RTE.default.skin = EXT:t3skin/rtehtmlarea/htmlarea.css
	RTE.default.FE.skin = EXT:t3skin/rtehtmlarea/htmlarea.css
');
