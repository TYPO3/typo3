<?php

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('reactions')) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        'sys_reaction',
        'table_name',
        [
            'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:tt_content',
            'tt_content',
            'mimetypes-x-content-text',
        ]
    );
}
