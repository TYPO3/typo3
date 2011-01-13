<?php
$extensionPath = t3lib_extMgm::extPath('linkvalidator');

return array(
	'tx_linkvalidator_linktypes_abstract' => $extensionPath . 'classes/linktypes/class.tx_linkvalidator_linktypes_abstract.php',
	'tx_linkvalidator_linktypes_interface' => $extensionPath . 'classes/linktypes/class.tx_linkvalidator_linktypes_interface.php',
	'tx_linkvalidator_linktypes_external' => $extensionPath . 'classes/linktypes/class.tx_linkvalidator_linktypes_external.php',
	'tx_linkvalidator_linktypes_file' => $extensionPath . 'classes/linktypes/class.tx_linkvalidator_linktypes_file.php',
	'tx_linkvalidator_linktypes_internal' => $extensionPath . 'classes/linktypes/class.tx_linkvalidator_linktypes_internal.php',
	'tx_linkvalidator_linktypes_linkhandler' => $extensionPath . 'classes/linktypes/class.tx_linkvalidator_linktypes_linkhandler.php',

	'tx_linkvalidator_processing' => $extensionPath . 'classes/class.tx_linkvalidator_processing.php',

	'tx_linkvalidator_tasks_validate'  => $extensionPath . 'classes/tasks/class.tx_linkvalidator_tasks_validate.php',
	'tx_linkvalidator_tasks_validateadditionalfieldprovider' => $extensionPath . 'classes/tasks/class.tx_linkvalidator_tasks_validateadditionalfieldprovider.php',
);
?>