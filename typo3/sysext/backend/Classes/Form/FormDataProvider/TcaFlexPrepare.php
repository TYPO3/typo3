<?php

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
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resolve flex data structure and data values, prepare and normalize.
 *
 * This is the first data provider in the chain of flex form related providers.
 */
readonly class TcaFlexPrepare implements FormDataProviderInterface
{
    public function __construct(
        private FlexFormTools $flexFormTools,
    ) {}

    /**
     * Resolve flex data structures and prepare flex data values.
     *
     * Normalize some details to have aligned array nesting for the rest of the
     * processing method and the render engine.
     */
    public function addData(array $result): array
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'flex') {
                continue;
            }
            $result = $this->initializeDataStructure($result, (string)$fieldName);
            $result = $this->initializeDataValues($result, (string)$fieldName);
        }
        return $result;
    }

    /**
     * Fetch / initialize data structure.
     *
     * The sub array with different possible data structures in ['config']['ds'] is
     * resolved here, ds array contains only the one resolved data structure after this method.
     */
    protected function initializeDataStructure(array $result, string $fieldName): array
    {
        if (!isset($result['processedTca']['columns'][$fieldName]['config']['dataStructureIdentifier'])) {
            $dataStructureIdentifier = null;
            $dataStructureArray = ['sheets' => ['sDEF' => []]];
            try {
                $dataStructureIdentifier = $this->flexFormTools->getDataStructureIdentifier(
                    $result['processedTca']['columns'][$fieldName],
                    $result['tableName'],
                    $fieldName,
                    $result['databaseRow']
                );
                $dataStructureArray = $this->flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
            } catch (InvalidIdentifierException) {
                $dataStructureIdentifier = null;
            } finally {
                // Add the identifier to TCA to use it later during rendering
                $result['processedTca']['columns'][$fieldName]['config']['dataStructureIdentifier'] = $dataStructureIdentifier;
            }
        } else {
            // Assume the data structure has been given from outside if the data structure identifier is already set.
            $dataStructureArray = $result['processedTca']['columns'][$fieldName]['config']['ds'];
        }
        if (!isset($dataStructureArray['meta']) || !is_array($dataStructureArray['meta'])) {
            $dataStructureArray['meta'] = [];
        }
        // This kicks one array depth:  config['ds']['listOfDataStructures'] becomes config['ds']
        // This also ensures the final ds can be found in 'ds', even if the DS was fetch from
        // a record, see FlexFormTools->getDataStructureIdentifier() for details.
        $result['processedTca']['columns'][$fieldName]['config']['ds'] = $dataStructureArray;
        return $result;
    }

    /**
     * Parse / initialize value from xml string to array
     */
    protected function initializeDataValues(array $result, string $fieldName): array
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
}
