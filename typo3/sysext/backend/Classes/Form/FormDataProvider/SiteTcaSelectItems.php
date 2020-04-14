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
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Service\IsoCodeService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Special data provider for the sites configuration module.
 *
 * Resolve some specialities of the "site configuration"
 */
class SiteTcaSelectItems implements FormDataProviderInterface
{
    /**
     * Resolve select items for
     * * 'site_language' -> 'typo3language'
     *
     * @param array $result
     * @return array
     * @throws \UnexpectedValueException
     */
    public function addData(array $result): array
    {
        $table = $result['tableName'];
        if ($table !== 'site_language') {
            return $result;
        }

        // Available languages from Locales class put as "typo3Language" items
        $locales = GeneralUtility::makeInstance(Locales::class);
        $languages = $locales->getLanguages();
        asort($languages);

        $items = [];
        foreach ($languages as $key => $label) {
            $items[] = [
                0 => $label,
                1 => $key,
            ];
        }
        $result['processedTca']['columns']['typo3Language']['config']['items'] = $items;

        // Available ISO-639-1 codes fetch from service class and put as "iso-639-1" items
        $isoItems = GeneralUtility::makeInstance(IsoCodeService::class)->renderIsoCodeSelectDropdown(['items' => []]);
        $result['processedTca']['columns']['iso-639-1']['config']['items'] = $isoItems['items'];

        return $result;
    }
}
