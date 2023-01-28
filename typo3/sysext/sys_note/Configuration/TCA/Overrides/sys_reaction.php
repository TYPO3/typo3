<?php

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('reactions')) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        'sys_reaction',
        'table_name',
        [
            'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note',
            'value' => 'sys_note',
            'icon' => 'mimetypes-x-sys_note',
        ]
    );
}
