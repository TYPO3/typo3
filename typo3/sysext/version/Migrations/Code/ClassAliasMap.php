<?php
return array(
	'tx_version_cm1' => 'TYPO3\\CMS\\Version\\Controller\\VersionModuleController',
	'tx_version_tcemain_CommandMap' => 'TYPO3\\CMS\\Version\\DataHandler\\CommandMap',
	't3lib_utility_Dependency_Factory' => 'TYPO3\\CMS\\Version\\Dependency\\DependencyEntityFactory',
	't3lib_utility_Dependency' => 'TYPO3\\CMS\\Version\\Dependency\\DependencyResolver',
	't3lib_utility_Dependency_Element' => 'TYPO3\\CMS\\Version\\Dependency\\ElementEntity',
	't3lib_utility_Dependency_Callback' => 'TYPO3\\CMS\\Version\\Dependency\\EventCallback',
	't3lib_utility_Dependency_Reference' => 'TYPO3\\CMS\\Version\\Dependency\\ReferenceEntity',
	'tx_version_tcemain' => 'TYPO3\\CMS\\Version\\Hook\\DataHandlerHook',
	'tx_version_iconworks' => 'TYPO3\\CMS\\Version\\Hook\\IconUtilityHook',
	'Tx_Version_Preview' => 'TYPO3\\CMS\\Version\\Hook\\PreviewHook',
	'tx_version_tasks_AutoPublish' => 'TYPO3\\CMS\\Version\\Task\\AutoPublishTask',
	'wslib' => 'TYPO3\\CMS\\Version\\Utility\\WorkspacesUtility',
	'tx_version_gui' => 'TYPO3\\CMS\\Version\\View\\VersionView',
);
?>