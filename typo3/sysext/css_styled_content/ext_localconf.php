<?php
# TYPO3 CVS ID: $Id$

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$_EXTCONF = unserialize($_EXTCONF);	// unserializing the configuration so we can use it here:
if ($_EXTCONF['setPageTSconfig'])	{
	t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:css_styled_content/pageTSconfig.txt">');
}

if ($_EXTCONF['removePositionTypes'])	{
	t3lib_extMgm::addPageTSConfig('
		TCEFORM.tt_content.imageorient.types.image.removeItems = 8,9,10,17,18,25,26
	');
}

$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['compat_version']['tx_cssstyledcontent_accessibility'] = array('version' => 3009000, 'description' => 'The rendering of the following elements has changed:<ul><li><b>tt_content.menu</b> (used f.e. for sitemaps): Instead of div-tags, lists are used now.</li><li><b>tt_content.mailform</b>: Mailforms do not use tables anymore, instead, they use the div-tag. Besides that, mailforms are accessible now.</li><li><b>The p-tag has been removed from all table cells.</b></li></ul>', 'description_acknowledge' => 'Yes, I know that I will need to update my stylesheets to comply with this change.');


?>