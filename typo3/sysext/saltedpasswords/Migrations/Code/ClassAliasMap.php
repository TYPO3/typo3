<?php
return array(
	'tx_saltedpasswords_autoloader' => 'TYPO3\\CMS\\Saltedpasswords\\Autoloader',
	'tx_saltedpasswords_eval_be' => 'TYPO3\\CMS\\Saltedpasswords\\Evaluation\\BackendEvaluator',
	'tx_saltedpasswords_eval' => 'TYPO3\\CMS\\Saltedpasswords\\Evaluation\\Evaluator',
	'tx_saltedpasswords_eval_fe' => 'TYPO3\\CMS\\Saltedpasswords\\Evaluation\\FrontendEvaluator',
	'tx_saltedpasswords_abstract_salts' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\AbstractSalt',
	'tx_saltedpasswords_salts_blowfish' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\BlowfishSalt',
	'tx_saltedpasswords_salts_md5' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\Md5Salt',
	'tx_saltedpasswords_salts_phpass' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\PhpassSalt',
	'tx_saltedpasswords_salts_factory' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\SaltFactory',
	'tx_saltedpasswords_salts' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\SaltInterface',
	'tx_saltedpasswords_sv1' => 'TYPO3\\CMS\\Saltedpasswords\\SaltedPasswordService',
	'tx_saltedpasswords_Tasks_BulkUpdate_AdditionalFieldProvider' => 'TYPO3\\CMS\\Saltedpasswords\\Task\\BulkUpdateFieldProvider',
	'tx_saltedpasswords_Tasks_BulkUpdate' => 'TYPO3\\CMS\\Saltedpasswords\\Task\\BulkUpdateTask',
	'tx_saltedpasswords_emconfhelper' => 'TYPO3\\CMS\\Saltedpasswords\\Utility\\ExtensionManagerConfigurationUtility',
	'tx_saltedpasswords_div' => 'TYPO3\\CMS\\Saltedpasswords\\Utility\\SaltedPasswordsUtility',
);
?>