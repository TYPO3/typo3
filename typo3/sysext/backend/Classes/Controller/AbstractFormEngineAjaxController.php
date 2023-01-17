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
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\JavaScriptItems;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
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
    protected function addJavaScriptModulesToJavaScriptItems(array $modules, JavaScriptItems $items, bool $deprecated = false): void
    {
        foreach ($modules as $module) {
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
            if ($deprecated) {
                trigger_error('FormEngine $resultArray[\'requireJsModules\'] is deprecated, use $resultArray[\'javaScriptsModules\'] instead. Support for this array key will be removed in TYPO3 v13.0.', E_USER_DEPRECATED);
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
    protected function getLabelsFromLocalizationFile(string $file): array
    {
        $languageService = $this->getLanguageService() ?? GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
        return $languageService->getLabelsFromResource($file);
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
