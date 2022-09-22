<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Controller;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Page\JavaScriptItems;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
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
    protected function addRegisteredRequireJsModulesToJavaScriptItems(array $result, JavaScriptItems $items): void
    {
        foreach ($result['requireJsModules'] ?? [] as $module) {
            if (!$module instanceof JavaScriptModuleInstruction) {
                throw new \LogicException(
                    sprintf(
                        'Module must be a %s, type "%s" given',
                        JavaScriptModuleInstruction::class,
                        gettype($module)
                    ),
                    1663851377
                );
            }
            $items->addJavaScriptModuleInstruction($module);
        }
    }

    /**
     * Resolve a CSS file position, possibly prefixed with 'EXT:'
     *
     * @param string $stylesheetFile Given file, possibly prefixed with EXT:
     * @return string Web root relative position to file
     */
    protected function getRelativePathToStylesheetFile(string $stylesheetFile): string
    {
        if (PathUtility::isExtensionPath($stylesheetFile)) {
            $stylesheetFile = GeneralUtility::getFileAbsFileName($stylesheetFile);
            $stylesheetFile = PathUtility::getRelativePathTo($stylesheetFile) ?? '';
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
        $languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);
        $language = $this->getLanguageService()->lang;
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

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
