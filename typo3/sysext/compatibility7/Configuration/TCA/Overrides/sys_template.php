<?php
defined('TYPO3_MODE') or die();

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('css_styled_content')) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'compatibility7',
        'Configuration/TypoScript/ContentElement/CssStyledContent/',
        'CSS Styled Content TYPO3 v7'
    );
}
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('fluid_styled_content')) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'compatibility7',
        'Configuration/TypoScript/ContentElement/FluidStyledContent/',
        'Fluid Styled Content TYPO3 v7'
    );
}
