<?php
$extensionPath = t3lib_extMgm::extPath('linkvalidator');

return array(
	'tx_linkvalidator_processing' => $extensionPath . 'lib/class.tx_linkvalidator_processing.php',
	'tx_linkvalidator_scheduler_linkadditionalfieldprovider' => $extensionPath . 'lib/class.tx_linkvalidator_scheduler_linkAdditionalFieldProvider.php',
	'tx_linkvalidator_scheduler_link'  => $extensionPath . 'lib/class.tx_linkvalidator_scheduler_link.php',
	'tx_linkvalidator_checkbase'  => $extensionPath . 'lib/class.tx_linkvalidator_checkbase.php',

);
?>