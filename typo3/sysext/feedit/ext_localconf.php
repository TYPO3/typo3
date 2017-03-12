<?php
defined('TYPO3_MODE') or die();

// Register the edit panel view.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/classes/class.frontendedit.php']['edit'] = \TYPO3\CMS\Feedit\FrontendEditPanel::class;

if (TYPO3_MODE === 'FE') {
    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)->connect(
        \TYPO3\CMS\Backend\Controller\EditDocumentController::class,
        'initAfter',
        \TYPO3\CMS\Feedit\FrontendEditAssetLoader::class,
        'attachAssets'
    );
}
