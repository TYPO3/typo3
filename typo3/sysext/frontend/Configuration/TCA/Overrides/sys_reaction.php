<?php

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('reactions')) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        'sys_reaction',
        'table_name',
        [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:tt_content',
            'value' => 'tt_content',
            'icon' => 'mimetypes-x-content-text',
        ]
    );
}
