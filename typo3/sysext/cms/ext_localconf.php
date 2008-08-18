<?php
# TYPO3 SVN ID: $Id$
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.pages = 0
	options.saveDocNew.pages_language_overlay = 0
');

$TYPO3_CONF_VARS['SYS']['contentTable'] = 'tt_content';
$TYPO3_CONF_VARS['FE']['eID_include']['tx_cms_showpic'] = 'EXT:cms/tslib/showpic.php';

$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['compat_version']['cms'] = array(
	'title' => 'CMS Frontend',
	'version' => 4000000,
	'description' => '<ul>' .
					'<li><p>Word separator character for simulateStaticDocument is changed from ' .
					'underscore (_) to hyphen (-) to make URLs more friendly for search engines' .
					'URLs that are already existing (e.g. external links to your site) will still work like before.</p>' .
					'<p>You can set the separator character back to an underscore by putting the following line into the '.
					'<b>Setup</b> section of your Page TypoScript template:</p>' .
					'<p style="margin-top: 5px; white-space: nowrap;"><code>config.simulateStaticDocuments_replacementChar = _</code></p></li>'.
					'<li><p>CSS Stylesheets and JavaScript are put into an external file by default.</p>'.
					'<p>Technically, that means that the default value of "config.inlineStyle2TempFile" is now set to "1" and that of "config.removeDefaultJS" to "external"</p></li>'.
					'</ul>',
);


	// registering hooks for the treelist cache
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:cms/tslib/hooks/class.tx_cms_treelistcacheupdate.php:&tx_cms_treelistCacheUpdate';
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][]  = 'EXT:cms/tslib/hooks/class.tx_cms_treelistcacheupdate.php:&tx_cms_treelistCacheUpdate';
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][]     = 'EXT:cms/tslib/hooks/class.tx_cms_treelistcacheupdate.php:&tx_cms_treelistCacheUpdate';


?>