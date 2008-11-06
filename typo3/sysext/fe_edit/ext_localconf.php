<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	// Register the admin panel and edit panel views.
$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/classes/class.frontendedit.php']['admin'] = 'EXT:fe_edit/view/class.tx_feedit_adminpanel.php:tx_feedit_adminpanel';
$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/classes/class.frontendedit.php']['edit'] = 'EXT:fe_edit/view/class.tx_feedit_editpanel.php:tx_feedit_editpanel';

?>