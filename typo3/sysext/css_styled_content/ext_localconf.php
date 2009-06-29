<?php
# TYPO3 CVS ID: $Id$

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$_EXTCONF = unserialize($_EXTCONF);	// unserializing the configuration so we can use it here:
if ($_EXTCONF['setPageTSconfig'] || !$_EXTCONF)	{
	t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:css_styled_content/pageTSconfig.txt">');
}

if ($_EXTCONF['removePositionTypes'] || !$_EXTCONF)	{
	t3lib_extMgm::addPageTSConfig('
		TCEFORM.tt_content.imageorient.types.image.removeItems = 8,9,10,17,18,25,26
	');
}

$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['compat_version']['tx_cssstyledcontent_accessibility'] = array(
	'title' => 'CSS Styled Content: Accessibility improvements',
	'version' => 3009000,
	'description' => '<p>The rendering of the following elements will change:
				<ul><li><b>tt_content.menu</b> (used f.e. for sitemaps): Instead of div-tags, lists are used now.</li>
				<li><b>tt_content.mailform</b>: Mailforms do not use tables anymore, instead, they use the div-tag. Besides that, mailforms are accessible now.</li>
				<li><b>The p-tag</b> has been removed from all table cells.</li>
				<li><b>CSS based "image" and "text with image"</b><br />As the extension cron_cssstyledimgtext has been merged into the core, rendering of the content elements "image" and "text with image" has been changed to be CSS instead of table based. Read the <a href="http://wiki.typo3.org/index.php/TYPO3_4.0" target="_blank">4.0 release notes</a> for further information.</li></ul>',
	'description_acknowledge' => 'You will have to update your stylesheets to comply with these changes.'
);
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['compat_version']['tx_cssstyledcontent_pagetargets'] = array(
	'title' => 'CSS Styled Content: Default targets for non-frame pages',
	'version' => 4002000,
	'description' => '<p>The default page target is empty (so no target is generated). If you use frames, you have to set target to "page" in Constants.</p>'
);
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['compat_version']['tx_cssstyledcontent_menuhtmlspecialchars'] = array(
	'title' => 'CSS Styled Content: htmlspecialchars in menu content elements',
	'version' => 4003000,
	'description' => '<p>Page titles will get htmlspecialchar\'ed when rendered in "Sitemap/menu" content elements, to avoid generating invalid XHTML.</p>',
);


?>