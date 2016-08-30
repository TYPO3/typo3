<?php
defined('TYPO3_MODE') or die();

// Populate available languages
/** @var $locales \TYPO3\CMS\Core\Localization\Locales */
$locales = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\Locales::class);
$languageItems = $locales->getLanguages();
unset($languageItems['default']);
asort($languageItems);
foreach ($languageItems as $locale => $name) {
    $GLOBALS['TCA']['be_users']['columns']['lang']['config']['items'][] = [$name, $locale];
}
