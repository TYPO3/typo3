<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Configuration\TCA;

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

use TYPO3\CMS\Core\Localization\LanguageService;

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
        $parameters['title'] = sprintf(
            '%s %s (%s) Base: %s',
            $record['enabled'] ? '' : '[' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:disabled') . ']',
            $record['title'],
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
        if ($record['type'][0] === 'uri') {
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
        switch ($record['errorHandler'][0]) {
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
                $arguments[] = $record['errorHandler'][0];
        }
        $parameters['title'] = sprintf($format, ...$arguments);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
