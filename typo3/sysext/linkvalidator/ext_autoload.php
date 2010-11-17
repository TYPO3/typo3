<?php
$extensionPath = t3lib_extMgm::extPath('linkvalidator');

return array(
	'tx_linkvalidator_processing' => $extensionPath . 'lib/class.tx_linkvalidator_processing.php',
	'tx_linkvalidator_checkbase'  => $extensionPath . 'lib/class.tx_linkvalidator_checkbase.php',

	'tx_linkvalidator_tasks_validate'  => $extensionPath . 'classes/tasks/class.tx_linkvalidator_tasks_validate.php',
	'tx_linkvalidator_tasks_validateadditionalfieldprovider' => $extensionPath . 'classes/tasks/class.tx_linkvalidator_tasks_validateadditionalfieldprovider.php',
);
?>