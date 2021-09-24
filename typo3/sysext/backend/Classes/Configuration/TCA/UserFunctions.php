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

namespace TYPO3\CMS\Backend\Configuration\TCA;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class provides user functions for the usage in TCA definition
 * @internal
 */
class UserFunctions
{
    /**
     * Used to build the IRRE title of a site language element
     *
     * @param array $parameters
     */
    public function getSiteLanguageTitle(array &$parameters): void
    {
        $record = $parameters['row'];
        $languageId = (int)($record['languageId'][0] ?? 0);

        if ($languageId === PHP_INT_MAX && str_starts_with((string)($record['uid'] ?? ''), 'NEW')) {
            // If we deal with a new record, created via "Create new" (indicated by the PHP_INT_MAX placeholder),
            // we use a label as record title, until the real values, especially the language ID, are calculated.
            $parameters['title'] = '[' . $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site.languages.new') . ']';
            return;
        }

        $parameters['title'] = sprintf(
            '%s %s [%d] (%s) Base: %s',
            $record['enabled'] ? '' : '[' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:disabled') . ']',
            $record['title'],
            $languageId,
            $record['locale'],
            $record['base']
        );
    }

    /**
     * Used to build the IRRE title of a site route element
     *
     * @param array $parameters
     */
    public function getRouteTitle(array &$parameters): void
    {
        $record = $parameters['row'];
        if (($record['type'][0] ?? false) === 'uri') {
            $parameters['title'] = sprintf(
                '%s %s %s',
                $record['route'],
                $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site.routes.irreHeader.redirectsTo'),
                $record['source'] ?: '[' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:undefined') . ']'
            );
        } else {
            $parameters['title'] = $record['route'];
        }
    }

    /**
     * Used to build the IRRE title of a site error handling element
     * @param array $parameters
     */
    public function getErrorHandlingTitle(array &$parameters): void
    {
        $record = $parameters['row'];
        $format = '%s: %s';
        $arguments = [$record['errorCode']];
        switch ($record['errorHandler'][0] ?? false) {
            case 'Fluid':
                $arguments[] = $record['errorFluidTemplate'];
                break;
            case 'Page':
                $arguments[] = $record['errorContentSource'];
                break;
            case 'PHP':
                $arguments[] = $record['errorPhpClassFQCN'];
                break;
            default:
                $arguments[] = $record['errorHandler'][0] ?? '';
        }
        $parameters['title'] = sprintf($format, ...$arguments);
    }

    public static function getAllSystemLocales(): array
    {
        $disabledFunctions = GeneralUtility::trimExplode(',', (string)ini_get('disable_functions'), true);
        if (in_array('exec', $disabledFunctions, true)) {
            return [];
        }

        $rawOutput = [];
        CommandUtility::exec('locale -a', $rawOutput);

        ksort($rawOutput, SORT_NATURAL);
        $locales = [];
        foreach ($rawOutput as $item) {
            $locales[] = [$item, $item];
        }

        return $locales;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
