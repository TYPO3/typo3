<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');


t3lib_extMgm::addPageTSConfig('
	RTE.default.skin = EXT:'.$_EXTKEY.'/rtehtmlarea/htmlarea.css
	RTE.default.FE.skin = EXT:'.$_EXTKEY.'/rtehtmlarea/htmlarea.css
');

?>