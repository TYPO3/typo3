<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// Extending TypoScript from static template uid=43 to set up parsing of custom file abstraction attributes on img tag
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($_EXTKEY, 'setup', '
	#******************************************************
	# Including library for processing of custom file abstraction attributes on img tag
	#******************************************************
	##includeLibs.tx_rtehtmlarea_renderimgtag = EXT:rtehtmlarea/extensions/TYPO3Image/class.tx_rtehtmlarea_renderimgtag.php

	lib.parseFunc_RTE {
		##tags.img = TEXT
		##tags.img {
		##	current = 1
		##	preUserFunc = tx_rtehtmlarea_renderimgtag->renderImgageAttributes
		##}
		nonTypoTagStdWrap.HTMLparser.tags.img.fixAttrib {
			data-htmlarea-file-uid.unset = 1
			data-htmlarea-file-table.unset = 1
		}
	}
', 43);
?>