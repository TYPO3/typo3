<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\DataHandling;

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

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * New records that are capable of handling slugs (TCA type 'slug'), always
 * require the field value to be set in order to run through the validation
 * process to create a new slug. Fields having `null` as value are ignored
 * and can be used to by-pass implicit slug initialization.
 *
 * @see DataHandler::fillInFieldArray(), DataHandler::checkValueForSlug()
 */
class SlugEnricher
{
    /**
     * @var array
     */
    protected $slugFieldNamesPerTable = [];

    /**
     * @param array $dataMap
     * @return array
     */
    public function enrichDataMap(array $dataMap): array
    {
        foreach ($dataMap as $tableName => &$tableDataMap) {
            $slugFieldNames = $this->resolveSlugFieldNames($tableName);
            if (empty($slugFieldNames)) {
                continue;
            }
            foreach ($tableDataMap as $identifier => &$fieldValues) {
                if (MathUtility::canBeInterpretedAsInteger($identifier)) {
                    continue;
                }
                $fieldValues = $this->enrichUndefinedSlugFieldNames(
                    $slugFieldNames,
                    $fieldValues
                );
            }
        }
        return $dataMap;
    }

    /**
     * @param array $slugFieldNames
     * @param array $fieldValues
     * @return array
     */
    protected function enrichUndefinedSlugFieldNames(array $slugFieldNames, array $fieldValues): array
    {
        if (empty($slugFieldNames)) {
            return [];
        }
        $undefinedSlugFieldNames = array_diff(
            $slugFieldNames,
            array_keys($fieldValues)
        );
        if (empty($undefinedSlugFieldNames)) {
            return $fieldValues;
        }
        return array_merge(
            $fieldValues,
            array_fill_keys(
                $undefinedSlugFieldNames,
                ''
            )
        );
    }

    /**
     * @param string $tableName
     * @return string[]
     */
    public function resolveSlugFieldNames(string $tableName): array
    {
        if (isset($this->slugFieldNamesPerTable[$tableName])) {
            return $this->slugFieldNamesPerTable[$tableName];
        }

        return $this->slugFieldNamesPerTable[$tableName] = array_keys(
            array_filter(
                $GLOBALS['TCA'][$tableName]['columns'] ?? [],
                function (array $settings) {
                    return ($settings['config']['type'] ?? null) === 'slug';
                }
            )
        );
    }
}
