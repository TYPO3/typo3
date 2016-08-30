<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * DataHandler DataSet
 */
class DataSet
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @param string $fileName
     * @param bool $applyDefaultValues
     * @return DataSet
     */
    public static function read($fileName, $applyDefaultValues = false)
    {
        $data = self::parseData(self::readData($fileName));

        if ($applyDefaultValues) {
            $data = self::applyDefaultValues($data);
        }

        return GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework\DataSet::class,
            $data
        );
    }

    /**
     * @param string $fileName
     * @return array
     * @throws \RuntimeException
     */
    protected static function readData($fileName)
    {
        if (!file_exists($fileName)) {
            throw new \RuntimeException('File "' . $fileName . '" does not exist');
        }

        $rawData = [];
        $fileHandle = fopen($fileName, 'r');
        while (($values = fgetcsv($fileHandle, 0)) !== false) {
            $rawData[] = $values;
        }
        fclose($fileHandle);
        return $rawData;
    }

    /**
     * Parses CSV data.
     *
     * Special values are:
     * + "\NULL" to treat as NULL value
     * + "\*" to ignore value during comparison
     *
     * @param array $rawData
     * @return array
     */
    protected static function parseData(array $rawData)
    {
        $data = [];
        $tableName = null;
        $fieldCount = null;
        $idIndex = null;
        foreach ($rawData as $values) {
            if (!empty($values[0])) {
                // Skip comment lines, starting with "#"
                if ($values[0]{0} === '#') {
                    continue;
                }
                $tableName = $values[0];
                $fieldCount = null;
                $idIndex = null;
                if (!isset($data[$tableName])) {
                    $data[$tableName] = [];
                }
            } elseif (implode('', $values) === '') {
                $tableName = null;
                $fieldCount = null;
                $idIndex = null;
            } elseif ($tableName !== null && !empty($values[1])) {
                array_shift($values);
                if (!isset($data[$tableName]['fields'])) {
                    $data[$tableName]['fields'] = [];
                    foreach ($values as $value) {
                        if (empty($value)) {
                            continue;
                        }
                        $data[$tableName]['fields'][] = $value;
                        $fieldCount = count($data[$tableName]['fields']);
                    }
                    if (in_array('uid', $values)) {
                        $idIndex = array_search('uid', $values);
                        $data[$tableName]['idIndex'] = $idIndex;
                    }
                } else {
                    if (!isset($data[$tableName]['elements'])) {
                        $data[$tableName]['elements'] = [];
                    }
                    $values = array_slice($values, 0, $fieldCount);
                    foreach ($values as &$value) {
                        if ($value === '\\NULL') {
                            $value = null;
                        }
                    }
                    unset($value);
                    $element = array_combine($data[$tableName]['fields'], $values);
                    if ($idIndex !== null) {
                        $data[$tableName]['elements'][$values[$idIndex]] = $element;
                    } else {
                        $data[$tableName]['elements'][] = $element;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Applies TCA default values to missing fields on the imported scenario data-set.
     * This is basically required for running the functional tests in a SQL strict mode environment.
     *
     * @param array $data
     * @return array
     */
    protected static function applyDefaultValues(array $data)
    {
        foreach ($data as $tableName => $sections) {
            if (empty($GLOBALS['TCA'][$tableName]['columns'])) {
                continue;
            }

            $fields = $sections['fields'];

            foreach ($GLOBALS['TCA'][$tableName]['columns'] as $tcaFieldName => $tcaFieldConfiguration) {
                // Skip if field was already imported
                if (in_array($tcaFieldName, $fields)) {
                    continue;
                }
                // Skip if field is an enable-column (it's expected that those fields have proper DBMS defaults)
                if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']) && in_array($tcaFieldName, $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'])) {
                    continue;
                }
                // Skip if no default value is defined in the accordant TCA definition (NULL values might occur as well)
                if (empty($tcaFieldConfiguration['config']) || !array_key_exists('default', $tcaFieldConfiguration['config'])) {
                    continue;
                }

                $data[$tableName]['fields'][] = $tcaFieldName;
                foreach ($data[$tableName]['elements'] as &$element) {
                    $element[$tcaFieldName] = $tcaFieldConfiguration['config']['default'];
                }
            }
        }
        return $data;
    }

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getTableNames()
    {
        return array_keys($this->data);
    }

    /**
     * @param string $tableName
     * @return NULL|array
     */
    public function getFields($tableName)
    {
        $fields = null;
        if (isset($this->data[$tableName]['fields'])) {
            $fields = $this->data[$tableName]['fields'];
        }
        return $fields;
    }

    /**
     * @param string $tableName
     * @return NULL|int
     */
    public function getIdIndex($tableName)
    {
        $idIndex = null;
        if (isset($this->data[$tableName]['idIndex'])) {
            $idIndex = $this->data[$tableName]['idIndex'];
        }
        return $idIndex;
    }

    /**
     * @param string $tableName
     * @return NULL|array
     */
    public function getElements($tableName)
    {
        $elements = null;
        if (isset($this->data[$tableName]['elements'])) {
            $elements = $this->data[$tableName]['elements'];
        }
        return $elements;
    }

    /**
     * @param string $fileName
     */
    public function persist($fileName)
    {
        $fileHandle = fopen($fileName, 'w');

        foreach ($this->data as $tableName => $tableData) {
            if (empty($tableData['fields']) || empty($tableData['elements'])) {
                continue;
            }

            $fields = $tableData['fields'];
            array_unshift($fields, '');

            fputcsv($fileHandle, [$tableName]);
            fputcsv($fileHandle, $fields);

            foreach ($tableData['elements'] as $element) {
                array_unshift($element, '');
                fputcsv($fileHandle, $element);
            }
        }

        fclose($fileHandle);
    }
}
