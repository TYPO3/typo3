<?php
# TYPO3 CVS ID: $Id$
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TYPO3_CONF_VARS['SYS']['contentTable'] = 'tt_content';
$TYPO3_CONF_VARS['FE']['eID_include']['tx_cms_showpic'] = 'EXT:cms/tslib/showpic.php';

$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['compat_version']['cms'] = array(
	'version' => 4000000,
	'description' => '<p>Word separator character for simulateStaticDocument is changed ' .
					'from underscore (_) to hyphen (-) to make URLs more friendly ' .
					'for search engines. Previously generated URLs (external links ' .
					'to your site) will work as before.</p><p>You can set separator ' .
					'character back to underscore by putting the following line into ' .
					'<b>Setup</b> section of you page TS template:</p><p style="margin-top: 5px">' .
					'<code>config.simulateStaticDocuments_replacementChar = _</code></p>',
	'description_acknowledge' => ''
);

?>