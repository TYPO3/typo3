<?php
return array(
	'tx_dbal_autoloader' => 'TYPO3\\CMS\\Dbal\\Autoloader',
	'tx_dbal_module1' => 'TYPO3\\CMS\\Dbal\\Controller\\ModuleController',
	'tx_dbal_tsparserext' => 'TYPO3\\CMS\\Dbal\\ExtensionManager\\MessageDisplay',
	'tx_dbal_em' => 'TYPO3\\CMS\\Dbal\\Hooks\\ExtensionManagerHooks',
	'tx_dbal_installtool' => 'TYPO3\\CMS\\Dbal\\Hooks\\InstallToolHooks',
	'tx_dbal_querycache' => 'TYPO3\\CMS\\Dbal\\QueryCache',
	'ux_t3lib_DB' => 'TYPO3\\CMS\\Dbal\\Database\\DatabaseConnection',
	'ux_t3lib_sqlparser' => 'TYPO3\\CMS\\Dbal\\Database\\SqlParser',
	'ux_localRecordList' => 'TYPO3\\CMS\\Dbal\\RecordList\\DatabaseRecordList',
);
?>