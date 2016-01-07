<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE' || TYPO3_MODE === 'FE' && isset($GLOBALS['BE_USER'])) {

    // Register as a skin
    $GLOBALS['TBE_STYLES']['skins']['t3skin'] = array(
        'name' => 't3skin',
        'stylesheetDirectories' => array(
            'sprites' => 'EXT:t3skin/stylesheets/sprites/',
            'css' => 'EXT:t3skin/Resources/Public/Css/'
        )
    );

    // extJS theme
    $GLOBALS['TBE_STYLES']['extJS']['theme'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('t3skin') . 'extjs/xtheme-t3skin.css';
}
