<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	// Register the edit panel view.
$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/classes/class.frontendedit.php']['edit'] = 'EXT:feedit/view/class.tx_feedit_editpanel.php:tx_feedit_editpanel';

?>