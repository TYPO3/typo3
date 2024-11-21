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
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidTcaException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Schema\Exception\UndefinedSchemaException;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
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
        private TcaSchemaFactory $tcaSchemaFactory,
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
     * The data structures in ['config']['ds'] is initialized here and the dataStructureIdentifier is set.
     */
    protected function initializeDataStructure(array $result, string $fieldName): array
    {
        if (!isset($result['processedTca']['columns'][$fieldName]['config']['dataStructureIdentifier'])) {
            $dataStructureArray = ['sheets' => ['sDEF' => []]];
            try {
                // Actually ['config']['ds'] might already contain the resolved data structure. However,
                // since the references value might be a file path and a couple of events exist for flex
                // form resolving, we nevertheless need to call getDataStructureIdentifier() and
                // parseDataStructureByIdentifier() here.
                $schema = $this->tcaSchemaFactory->get($result['tableName']);
                $dataStructureIdentifier = $this->flexFormTools->getDataStructureIdentifier(
                    $result['processedTca']['columns'][$fieldName],
                    $result['tableName'],
                    $fieldName,
                    $result['databaseRow'],
                    $schema
                );
                $dataStructureArray = $this->flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier, $schema);
                // Add the identifier to TCA to use it later during rendering
                $result['processedTca']['columns'][$fieldName]['config']['dataStructureIdentifier'] = $dataStructureIdentifier;
            } catch (InvalidTcaException|InvalidIdentifierException|UndefinedSchemaException) {
                // Skip the data structure if it is invalid
            }
        } else {
            // Assume the data structure has been given from outside if the data structure identifier is already set.
            $dataStructureArray = $result['processedTca']['columns'][$fieldName]['config']['ds'];
        }
        if (!isset($dataStructureArray['meta']) || !is_array($dataStructureArray['meta'])) {
            $dataStructureArray['meta'] = [];
        }
        // Finally add the resolved data Structure to "ds"
        $result['processedTca']['columns'][$fieldName]['config']['ds'] = $dataStructureArray;
        return $result;
    }

    /**
     * Parse / initialize value from xml string to array
     */
    protected function initializeDataValues(array $result, string $fieldName): array
    {
        $valueArray = [];

        if (isset($result['databaseRow'][$fieldName]) && $result['databaseRow'][$fieldName] !== '') {
            if (is_array($result['databaseRow'][$fieldName])) {
                $valueArray = $result['databaseRow'][$fieldName];
            } else {
                $valueArray = GeneralUtility::xml2array($result['databaseRow'][$fieldName]);
                if (!is_array($valueArray)) {
                    $valueArray = [];
                }
            }
        }
        $valueArray['data'] ??= [];
        $valueArray['meta'] ??= [];
        $result['databaseRow'][$fieldName] = $valueArray;
        return $result;
    }
}
