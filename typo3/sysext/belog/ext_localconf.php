<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)
    ->connect(
        \TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem::class,
        'loadMessages',
        \TYPO3\CMS\Belog\Controller\SystemInformationController::class,
        'appendMessage'
    );
