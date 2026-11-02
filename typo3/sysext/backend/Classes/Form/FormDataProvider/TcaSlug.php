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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Schema\LanguageMarker;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handles custom data for TCA Type=Slug
 */
class TcaSlug implements FormDataProviderInterface
{
    /**
     * Resolve slug prefix items
     *
     *
     * @throws \UnexpectedValueException
     */
    public function addData(array $result): array
    {
        $table = $result['tableName'];
        $site = $result['site'];
        $row = $result['databaseRow'];
        $languageId = LanguageMarker::DEFAULT_LANGUAGE;

        if (($languageField = $result['processedTca']['ctrl']['languageField'] ?? '') !== '' && isset($row[$languageField])) {
            $languageId = (int)(is_array($row[$languageField]) ? ($row[$languageField][0] ?? LanguageMarker::DEFAULT_LANGUAGE) : $row[$languageField]);
        }

        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (($fieldConfig['config']['type'] ?? '') !== 'slug') {
                continue;
            }

            $prefixUserFunc = $fieldConfig['config']['appearance']['prefix'] ?? '';

            if ($prefixUserFunc !== '') {
                $parameters = [
                    'site' => $site,
                    'languageId' => $languageId,
                    'table' => $table,
                    'row' => $row,
                    'fieldName' => $fieldName,
                    'config' => $fieldConfig['config'],
                ];
                $prefix = GeneralUtility::callUserFunction($prefixUserFunc, $parameters, $this);
            } elseif ($site instanceof SiteInterface) {
                // default behaviour used for pages
                $prefix = $this->getPrefixForSite($site, $languageId);
            } else {
                // no site found, so we cannot determine a prefix
                $prefix = '';
            }

            $result['customData'][$fieldName]['slugPrefix'] = $prefix;
            $result['processedTca']['columns'][$fieldName]['config']['appearance']['prefix'] = $prefix;
        }

        return $result;
    }

    /**
     * Render the prefix for the input field.
     */
    protected function getPrefixForSite(SiteInterface $site, int $languageId): string
    {
        try {
            $language = ($languageId < LanguageMarker::DEFAULT_LANGUAGE) ? $site->getDefaultLanguage() : $site->getLanguageById($languageId);
            $base = $language->getBase();
            $prefix = rtrim((string)$base, '/');
            if ($prefix !== '' && empty($base->getScheme()) && $base->getHost() !== '') {
                $prefix = 'http:' . $prefix;
            }
        } catch (\InvalidArgumentException $e) {
            // No site found
            $prefix = '';
        }

        return $prefix;
    }
}
