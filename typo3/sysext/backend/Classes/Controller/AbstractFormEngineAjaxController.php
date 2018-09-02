<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Abstract class for a couple of FormEngine controllers triggered by
 * ajax calls. The class containers some helpers to for instance prepare
 * the form render result for json output.
 *
 * @internal Marked as internal for now, methods in this class may change any time.
 */
abstract class AbstractFormEngineAjaxController
{
    /**
     * Gets result array from FormEngine and returns string with js modules
     * that need to be loaded and evaluated by JavaScript.
     *
     * @param array $result
     * @return array
     */
    protected function createExecutableStringRepresentationOfRegisteredRequireJsModules(array $result): array
    {
        if (empty($result['requireJsModules'])) {
            return [];
        }
        $requireJs = [];
        foreach ($result['requireJsModules'] as $module) {
            $moduleName = null;
            $callback = null;
            if (is_string($module)) {
                // if $module is a string, no callback
                $moduleName = $module;
                $callback = null;
            } elseif (is_array($module)) {
                // if $module is an array, callback is possible
                foreach ($module as $key => $value) {
                    $moduleName = $key;
                    $callback = $value;
                    break;
                }
            }
            if ($moduleName !== null) {
                $inlineCodeKey = $moduleName;
                $javaScriptCode = 'require(["' . $moduleName . '"]';
                if ($callback !== null) {
                    $inlineCodeKey .= sha1($callback);
                    $javaScriptCode .= ', ' . $callback;
                }
                $javaScriptCode .= ');';
                $requireJs[] = '/*RequireJS-Module-' . $inlineCodeKey . '*/' . LF . $javaScriptCode;
            }
        }
        return $requireJs;
    }

    /**
     * Resolve a CSS file position, possibly prefixed with 'EXT:'
     *
     * @param string $stylesheetFile Given file, possibly prefixed with EXT:
     * @return string Web root relative position to file
     */
    protected function getRelativePathToStylesheetFile(string $stylesheetFile): string
    {
        if (strpos($stylesheetFile, 'EXT:') === 0) {
            $stylesheetFile = GeneralUtility::getFileAbsFileName($stylesheetFile);
            $stylesheetFile = PathUtility::getRelativePathTo($stylesheetFile);
            $stylesheetFile = rtrim($stylesheetFile, '/');
        } else {
            $stylesheetFile = GeneralUtility::resolveBackPath($stylesheetFile);
        }
        $stylesheetFile = GeneralUtility::createVersionNumberedFilename($stylesheetFile);
        return PathUtility::getAbsoluteWebPath($stylesheetFile);
    }

    /**
     * Parse a language file and get a label/value array from it.
     *
     * @param string $file EXT:path/to/file
     * @return array Label/value array
     */
    protected function getLabelsFromLocalizationFile($file)
    {
        /** @var LocalizationFactory $languageFactory */
        $languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);
        $language = $GLOBALS['LANG']->lang;
        $localizationArray = $languageFactory->getParsedData($file, $language);
        if (is_array($localizationArray) && !empty($localizationArray)) {
            if (!empty($localizationArray[$language])) {
                $xlfLabelArray = $localizationArray['default'];
                ArrayUtility::mergeRecursiveWithOverrule($xlfLabelArray, $localizationArray[$language], true, false);
            } else {
                $xlfLabelArray = $localizationArray['default'];
            }
        } else {
            $xlfLabelArray = [];
        }
        $labelArray = [];
        foreach ($xlfLabelArray as $key => $value) {
            if (isset($value[0]['target'])) {
                $labelArray[$key] = $value[0]['target'];
            } else {
                $labelArray[$key] = '';
            }
        }
        return $labelArray;
    }
}
