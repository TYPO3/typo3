<?php
# TYPO3 CVS ID: $Id$

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$_EXTCONF = unserialize($_EXTCONF);	// unserializing the configuration so we can use it here:
if ($_EXTCONF['setPageTSconfig'])	{
	t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:css_styled_content/pageTSconfig.txt">');
}
?>