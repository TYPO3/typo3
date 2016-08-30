<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resolve and flex data structure and data values.
 *
 * This is the first data provider in the chain of flex form related providers.
 */
class TcaFlexFetch implements FormDataProviderInterface
{
    /**
     * Resolve ds pointer stuff and parse both ds and dv
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'flex') {
                continue;
            }
            $result = $this->initializeDataStructure($result, $fieldName);
            $result = $this->initializeDataValues($result, $fieldName);
            $result = $this->resolvePossibleExternalFileInDataStructure($result, $fieldName);
        }

        return $result;
    }

    /**
     * Fetch / initialize data structure.
     *
     * The sub array with different possible data structures in ['config']['ds'] is
     * resolved here, ds array contains only the one resolved data structure after this method.
     *
     * @param array $result Result array
     * @param string $fieldName Currently handled field name
     * @return array Modified result
     * @throws \UnexpectedValueException
     */
    protected function initializeDataStructure(array $result, $fieldName)
    {
        // Fetch / initialize data structure
        $dataStructureArray = BackendUtility::getFlexFormDS(
            $result['processedTca']['columns'][$fieldName]['config'],
            $result['databaseRow'],
            $result['tableName'],
            $fieldName
        );
        // If data structure can't be parsed, this is a developer error, so throw a non catchable exception
        if (!is_array($dataStructureArray)) {
            throw new \UnexpectedValueException(
                'Data structure error: ' . $dataStructureArray,
                1440506893
            );
        }
        if (!isset($dataStructureArray['meta']) || !is_array($dataStructureArray['meta'])) {
            $dataStructureArray['meta'] = [];
        }
        // This kicks one array depth:  config['ds']['matchingIdentifier'] becomes config['ds']
        $result['processedTca']['columns'][$fieldName]['config']['ds'] = $dataStructureArray;
        return $result;
    }

    /**
     * Parse / initialize value from xml string to array
     *
     * @param array $result Result array
     * @param string $fieldName Currently handled field name
     * @return array Modified result
     */
    protected function initializeDataValues(array $result, $fieldName)
    {
        if (!array_key_exists($fieldName, $result['databaseRow'])) {
            $result['databaseRow'][$fieldName] = '';
        }
        $valueArray = [];
        if (isset($result['databaseRow'][$fieldName])) {
            $valueArray = $result['databaseRow'][$fieldName];
        }
        if (!is_array($result['databaseRow'][$fieldName])) {
            $valueArray = GeneralUtility::xml2array($result['databaseRow'][$fieldName]);
        }
        if (!is_array($valueArray)) {
            $valueArray = [];
        }
        if (!isset($valueArray['data'])) {
            $valueArray['data'] = [];
        }
        if (!isset($valueArray['meta'])) {
            $valueArray['meta'] = [];
        }
        $result['databaseRow'][$fieldName] = $valueArray;
        return $result;
    }

    /**
     * Single fields can be extracted to files again. This is resolved and parsed here.
     *
     * @todo: Why is this not done in BackendUtility::getFlexFormDS() directly? If done there, the two methods
     * @todo: GeneralUtility::resolveSheetDefInDS() and GeneralUtility::resolveAllSheetsInDS() could be killed
     * @todo: since this resolving is basically the only really useful thing they actually do.
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @return array Modified item array
     */
    protected function resolvePossibleExternalFileInDataStructure(array $result, $fieldName)
    {
        $modifiedDataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
        if (isset($modifiedDataStructure['sheets']) && is_array($modifiedDataStructure['sheets'])) {
            foreach ($modifiedDataStructure['sheets'] as $sheetName => $sheetStructure) {
                if (!is_array($sheetStructure)) {
                    $file = GeneralUtility::getFileAbsFileName($sheetStructure);
                    if ($file && @is_file($file)) {
                        $sheetStructure = GeneralUtility::xml2array(GeneralUtility::getUrl($file));
                    }
                }
                $modifiedDataStructure['sheets'][$sheetName] = $sheetStructure;
            }
        }
        $result['processedTca']['columns'][$fieldName]['config']['ds'] = $modifiedDataStructure;
        return $result;
    }
}
