<?php
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('linkvalidator');
return array(
	'tx_linkvalidator_linktype_abstract' => $extensionPath . 'classes/linktype/class.tx_linkvalidator_linktype_abstract.php',
	'tx_linkvalidator_linktype_interface' => $extensionPath . 'classes/linktype/class.tx_linkvalidator_linktype_interface.php',
	'tx_linkvalidator_linktype_external' => $extensionPath . 'classes/linktype/class.tx_linkvalidator_linktype_external.php',
	'tx_linkvalidator_linktype_file' => $extensionPath . 'classes/linktype/class.tx_linkvalidator_linktype_file.php',
	'tx_linkvalidator_linktype_internal' => $extensionPath . 'classes/linktype/class.tx_linkvalidator_linktype_internal.php',
	'tx_linkvalidator_linktype_linkhandler' => $extensionPath . 'classes/linktype/class.tx_linkvalidator_linktype_linkhandler.php',
	'tx_linkvalidator_processor' => $extensionPath . 'classes/class.tx_linkvalidator_processor.php',
	'tx_linkvalidator_tasks_validator' => $extensionPath . 'classes/tasks/class.tx_linkvalidator_tasks_validator.php',
	'tx_linkvalidator_tasks_validatoradditionalfieldprovider' => $extensionPath . 'classes/tasks/class.tx_linkvalidator_tasks_validatoradditionalfieldprovider.php'
);
?>