<?php
defined('TYPO3_MODE') or die();

// Open extension manual from within Extension Manager
\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)
    ->connect(
        \TYPO3\CMS\Extensionmanager\ViewHelpers\ProcessAvailableActionsViewHelper::class,
        'processActions',
        \TYPO3\CMS\Documentation\Slots\ExtensionManager::class,
        'processActions'
    );
