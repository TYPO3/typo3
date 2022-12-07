<?php

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('reactions')) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        'sys_reaction',
        'table_name',
        [
            'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note',
            'sys_note',
            'mimetypes-x-sys_note',
        ]
    );
}
