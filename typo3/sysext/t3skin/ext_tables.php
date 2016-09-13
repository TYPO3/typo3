<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE' || TYPO3_MODE === 'FE' && isset($GLOBALS['BE_USER'])) {
    // extJS theme
    $GLOBALS['TBE_STYLES']['extJS']['theme'] = 'EXT:t3skin/extjs/xtheme-t3skin.css';
}
