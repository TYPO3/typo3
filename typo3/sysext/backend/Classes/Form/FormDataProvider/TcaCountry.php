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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Form\Processor\SelectItemProcessor;
use TYPO3\CMS\Core\Country\CountryFilter;
use TYPO3\CMS\Core\Country\CountryProvider;

/**
 * Resolve select items for the type="country" and set processed item list in processedTca
 */
#[Autoconfigure(public: true)]
final class TcaCountry extends AbstractItemProvider implements FormDataProviderInterface
{
    public function __construct(
        private readonly CountryProvider $countryProvider,
        private readonly SelectItemProcessor $selectItemProcessor,
    ) {}

    /**
     * Fetch countries to add them as select item
     *
     * @throws \UnexpectedValueException
     */
    public function addData(array $result): array
    {
        $table = $result['tableName'];

        $languageService = $this->getLanguageService();

        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (!isset($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'country') {
                continue;
            }

            $allItems = [];
            $filter = new CountryFilter($fieldConfig['config']['filter']['excludeCountries'] ?? [], $fieldConfig['config']['filter']['onlyCountries'] ?? []);
            $allCountries = $this->countryProvider->getFiltered($filter);

            foreach ($allCountries as $country) {
                $code = $country->getAlpha2IsoCode();
                switch ($fieldConfig['config']['labelField'] ?? 'name') {
                    case 'localizedName':
                        $allItems[$code] = $languageService->sL($country->getLocalizedNameLabel());
                        break;
                    case 'name':
                        $allItems[$code] = $country->getName();
                        break;
                    case 'iso2':
                        $allItems[$code] = $country->getAlpha2IsoCode();
                        break;
                    case 'iso3':
                        $allItems[$code] = $country->getAlpha3IsoCode();
                        break;
                    case 'officialName':
                        $allItems[$code] = $country->getOfficialName() ?? $country->getName();
                        break;
                    case 'localizedOfficialName':
                        $name = $languageService->sL($country->getLocalizedOfficialNameLabel());
                        if (!$name) {
                            $name = $languageService->sL($country->getLocalizedNameLabel());
                        }
                        $allItems[$code] = $name;
                        break;
                    default:
                        throw new \UnexpectedValueException(
                            'Setting "labelField" must either be set to "localizedName", "name", "iso2", "iso3", "officialName", or "localizedOfficialName".',
                            1675895616
                        );
                }
            }

            $prioritizedItems = [];
            if (is_array($fieldConfig['config']['prioritizedCountries'] ?? false) && !empty($fieldConfig['config']['prioritizedCountries'])) {
                foreach ($fieldConfig['config']['prioritizedCountries'] as $countryCode) {
                    if (isset($allItems[$countryCode])) {
                        $label = $allItems[$countryCode];
                        $prioritizedItems[$countryCode] = $label;
                        unset($allItems[$countryCode]);
                    }
                }
            }
            $items = [];
            $useItemGroups = !empty($prioritizedItems);

            // When not required, prefix an empty value
            if (!($fieldConfig['config']['required'] ?? false)) {
                $items[''] = '';
            }

            $this->addItem($items, $prioritizedItems, $allCountries, $useItemGroups ? 'prioritized' : '');
            $this->addItem($items, $allItems, $allCountries, $useItemGroups ? 'default' : '');
            $fieldConfig['config']['items'] = $items;

            $itemGroups = [
                'prioritized' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:countries.prioritized',
                'default' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:countries.default',
            ];

            // Respect TSconfig options
            $fieldConfig['config']['items'] = $this->removeItemsByKeepItemsPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->addItemsFromPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->removeItemsByRemoveItemsPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);

            // In case no items are set at this point, we can write this back and continue with the next column
            if ($fieldConfig['config']['items'] === []) {
                $result['processedTca']['columns'][$fieldName] = $fieldConfig;
                continue;
            }

            // Translate labels
            $fieldConfig['config']['items'] = $this->translateLabels($result, $fieldConfig['config']['items'], $table, $fieldName);
            $fieldConfig['config']['items'] = $this->selectItemProcessor->groupAndSortItems(
                $fieldConfig['config']['items'],
                $itemGroups,
                $fieldConfig['config']['sortItems'] ?? []
            );
            $result['processedTca']['columns'][$fieldName] = $fieldConfig;
        }

        return $result;
    }

    private function addItem(array &$items, array $list, array $allCountries, string $group = ''): void
    {
        foreach ($list as $key => $label) {
            $option = [
                'label' => $label,
                'value' => $key,
                'icon' => ($key !== '') ? 'flags-' . strtolower($allCountries[$key]->getAlpha2IsoCode()) : '',
            ];
            if ($group !== '') {
                $option['group'] = $group;
            }
            $items[] = $option;
        }
    }
}
