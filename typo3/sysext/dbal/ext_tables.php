<?php
defined('TYPO3_MODE') or die();

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['dbal'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['dbal'] = [];
}
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['dbal'][] = \TYPO3\CMS\Dbal\Report\DbalStatus::class;

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'tools',
        'txdbalM1',
        '',
        '',
        [
            'routeTarget' => \TYPO3\CMS\Dbal\Controller\ModuleController::class . '::mainAction',
            'access' => 'admin',
            'name' => 'tools_txdbalM1',
            'icon' => 'EXT:dbal/Resources/Public/Icons/module-dbal.svg',
            'labels' => 'LLL:EXT:dbal/Resources/Private/Language/locallang_mod.xlf'
        ]
    );
}
