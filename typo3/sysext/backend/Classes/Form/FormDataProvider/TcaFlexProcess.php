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

use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\FlexFormSegment;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Process data structures and data values, calculate defaults.
 *
 * This is typically the last provider, executed after TcaFlexPrepare
 */
class TcaFlexProcess implements FormDataProviderInterface
{
    /**
     * Determine possible pageTsConfig overrides and apply them to ds.
     * Determine available languages and sanitize dv for further processing. Then kick
     * and validate further details like excluded fields. Finally for each possible
     * value and ds call FormDataCompiler with set FlexFormSegment group to resolve
     * single field stuff like item processor functions.
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

            $flexIdentifier = $this->getFlexIdentifier($result, $fieldName);
            $pageTsConfigOfFlex = $this->getPageTsOfFlex($result, $fieldName, $flexIdentifier);
            $result = $this->modifyOuterDataStructure($result, $fieldName, $pageTsConfigOfFlex);
            $result = $this->removeExcludeFieldsFromDataStructure($result, $fieldName, $flexIdentifier);
            $result = $this->removeDisabledFieldsFromDataStructure($result, $fieldName, $pageTsConfigOfFlex);
            $result = $this->modifyDataStructureAndDataValuesByFlexFormSegmentGroup($result, $fieldName, $pageTsConfigOfFlex);
            $result = $this->addDataStructurePointersToMetaData($result, $fieldName);
        }

        return $result;
    }

    /**
     * Take care of ds_pointerField and friends to determine the correct sub array within
     * TCA config ds.
     *
     * Gets extension identifier. Use second pointer field if it's value is not empty, "list" or "*",
     * else it must be a plugin and first one will be used.
     * This code basically determines the sub key of ds field:
     * config = array(
     *  ds => array(
     *    'aFlexConfig' => '<flexXml ...
     *     ^^^^^^^^^^^
     * $flexformIdentifier contains "aFlexConfig" after this operation.
     *
     * @todo: This method is only implemented half. It basically should do all the
     * @todo: pointer handling that is done within BackendUtility::getFlexFormDS() to $srcPointer.
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @return string Pointer
     */
    protected function getFlexIdentifier(array $result, $fieldName)
    {
        // @todo: Current implementation with the "list_type, CType" fallback is rather limited and customized for
        // @todo: tt_content, also it forces a ds_pointerField to be defined and a casual "default" sub array does not work
        $pointerFields = !empty($result['processedTca']['columns'][$fieldName]['config']['ds_pointerField'])
            ? $result['processedTca']['columns'][$fieldName]['config']['ds_pointerField']
            : 'list_type,CType';
        $pointerFields = GeneralUtility::trimExplode(',', $pointerFields);
        $flexformIdentifier = !empty($result['databaseRow'][$pointerFields[0]]) ? $result['databaseRow'][$pointerFields[0]] : '';
        if (!empty($result['databaseRow'][$pointerFields[1]])
            && $result['databaseRow'][$pointerFields[1]] !== 'list'
            && $result['databaseRow'][$pointerFields[1]] !== '*'
        ) {
            $flexformIdentifier = $result['databaseRow'][$pointerFields[1]];
        }
        if (empty($flexformIdentifier)) {
            $flexformIdentifier = 'default';
        }

        return $flexformIdentifier;
    }

    /**
     * Determine TCEFORM.aTable.aField.matchingIdentifier
     *
     * @param array $result Result array
     * @param string $fieldName Handled field name
     * @param string $flexIdentifier Determined identifier
     * @return array PageTsConfig for this flex
     */
    protected function getPageTsOfFlex(array $result, $fieldName, $flexIdentifier)
    {
        $table = $result['tableName'];
        $pageTs = [];
        if (!empty($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.'][$flexIdentifier . '.'])
            && is_array($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.'][$flexIdentifier . '.'])) {
            $pageTs = $result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.'][$flexIdentifier . '.'];
        }
        return $pageTs;
    }

    /**
     * Handle "outer" flex data structure changes like language and sheet
     * description. Does not change "TCA" or values of single elements
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $pageTsConfig Given pageTsConfig of this flex form
     * @return array Modified item array
     */
    protected function modifyOuterDataStructure(array $result, $fieldName, $pageTsConfig)
    {
        $modifiedDataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];

        if (isset($modifiedDataStructure['sheets']) && is_array($modifiedDataStructure['sheets'])) {
            // Handling multiple sheets
            foreach ($modifiedDataStructure['sheets'] as $sheetName => $sheetStructure) {
                if (isset($pageTsConfig[$sheetName . '.']) && is_array($pageTsConfig[$sheetName . '.'])) {
                    $pageTsOfSheet = $pageTsConfig[$sheetName . '.'];

                    // Remove whole sheet if disabled
                    if (!empty($pageTsOfSheet['disabled'])) {
                        unset($modifiedDataStructure['sheets'][$sheetName]);
                        continue;
                    }

                    // sheetTitle, sheetDescription, sheetShortDescr
                    $modifiedDataStructure['sheets'][$sheetName] = $this->modifySingleSheetInformation($sheetStructure, $pageTsOfSheet);
                }
            }
        }

        $result['processedTca']['columns'][$fieldName]['config']['ds'] = $modifiedDataStructure;

        return $result;
    }

    /**
     * Removes fields from data structure the user has no access to
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param string $flexIdentifier Determined identifier
     * @return array Modified result
     */
    protected function removeExcludeFieldsFromDataStructure(array $result, $fieldName, $flexIdentifier)
    {
        $dataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
        $backendUser = $this->getBackendUser();
        if ($backendUser->isAdmin() || !isset($dataStructure['sheets']) || !is_array($dataStructure['sheets'])) {
            return $result;
        }

        $userNonExcludeFields = GeneralUtility::trimExplode(',', $backendUser->groupData['non_exclude_fields']);
        $excludeFieldsPrefix = $result['tableName'] . ':' . $fieldName . ';' . $flexIdentifier . ';';
        $nonExcludeFields = [];
        foreach ($userNonExcludeFields as $userNonExcludeField) {
            if (strpos($userNonExcludeField, $excludeFieldsPrefix) !== false) {
                $exploded = explode(';', $userNonExcludeField);
                $sheetName = $exploded[2];
                $allowedFlexFieldName = $exploded[3];
                $nonExcludeFields[$sheetName][$allowedFlexFieldName] = true;
            }
        }
        foreach ($dataStructure['sheets'] as $sheetName => $sheetDefinition) {
            if (!isset($sheetDefinition['ROOT']['el']) || !is_array($sheetDefinition['ROOT']['el'])) {
                continue;
            }
            foreach ($sheetDefinition['ROOT']['el'] as $flexFieldName => $fieldDefinition) {
                if (!empty($fieldDefinition['exclude']) && !isset($nonExcludeFields[$sheetName][$flexFieldName])) {
                    unset($result['processedTca']['columns'][$fieldName]['config']['ds']['sheets'][$sheetName]['ROOT']['el'][$flexFieldName]);
                }
            }
        }

        return $result;
    }

    /**
     * Remove fields from data structure that are disabled in pageTsConfig.
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $pageTsConfig Given pageTsConfig of this flex form
     * @return array Modified item array
     */
    protected function removeDisabledFieldsFromDataStructure(array $result, $fieldName, $pageTsConfig)
    {
        $dataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
        if (!isset($dataStructure['sheets']) || !is_array($dataStructure['sheets'])) {
            return $result;
        }
        foreach ($dataStructure['sheets'] as $sheetName => $sheetDefinition) {
            if (!isset($sheetDefinition['ROOT']['el']) || !is_array($sheetDefinition['ROOT']['el'])
                || !isset($pageTsConfig[$sheetName . '.'])) {
                continue;
            }
            foreach ($sheetDefinition['ROOT']['el'] as $flexFieldName => $fieldDefinition) {
                if (!empty($pageTsConfig[$sheetName . '.'][$flexFieldName . '.']['disabled'])) {
                    unset($result['processedTca']['columns'][$fieldName]['config']['ds']['sheets'][$sheetName]['ROOT']['el'][$flexFieldName]);
                }
            }
        }
        return $result;
    }

    /**
     * Feed single flex field and data to FlexFormSegment FormData compiler and merge result.
     * This one is nasty. Goal is to have processed TCA stuff in DS and also have validated / processed data values.
     *
     * Three main parts in this method:
     * * Process values of existing section container for default values
     * * Process values and TCA of possible section container and create a default value row for each
     * * Process TCA of "normal" fields and have default values in data ['templateRows']['containerName'] parallel to section ['el']
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $pageTsConfig Given pageTsConfig of this flex form
     * @return array Modified item array
     * @throws \UnexpectedValueException
     */
    protected function modifyDataStructureAndDataValuesByFlexFormSegmentGroup(array $result, $fieldName, $pageTsConfig)
    {
        $dataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
        $dataValues = $result['databaseRow'][$fieldName];
        $tableName = $result['tableName'];

        if (!isset($dataStructure['sheets']) || !is_array($dataStructure['sheets'])) {
            return $result;
        }

        /** @var FlexFormSegment $formDataGroup */
        $formDataGroup = GeneralUtility::makeInstance(FlexFormSegment::class);
        /** @var FormDataCompiler $formDataCompiler */
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);

        foreach ($dataStructure['sheets'] as $dataStructureSheetName => $dataStructureSheetDefinition) {
            if (!isset($dataStructureSheetDefinition['ROOT']['el']) || !is_array($dataStructureSheetDefinition['ROOT']['el'])) {
                continue;
            }
            $dataStructureSheetElements = $dataStructureSheetDefinition['ROOT']['el'];

            // Prepare pageTsConfig of this sheet
            $pageTsConfig['TCEFORM.'][$tableName . '.'] = [];
            if (isset($pageTsConfig[$dataStructureSheetName . '.']) && is_array($pageTsConfig[$dataStructureSheetName . '.'])) {
                $pageTsConfig['TCEFORM.'][$tableName . '.'] = $pageTsConfig[$dataStructureSheetName . '.'];
            }

            // It is possible to have a flex field field with of foreign_table (eg. type=select) that has markers in
            // a foreign_table_where like ###PAGE_TSCONFIG_ID###. It was possible to set this in page TSConfig for flex fields like this:
            // TCEFORM.theTable.theFlexfield.PAGE_TSCONFIG_ID = 42
            // This hands over this PAGE_TSCONFIG_ID to all flex fields that have this foreign_table_where marker.
            // This is a contradiction to the "usual" page TSConfig flex configuration that should be done for single flex fields:
            // TCEFORM.theTable.theFlexfield.theDataStructure.theSheet.theField.PAGE_TSCONFIG_ID = 42
            // The below code is a hack to still simulate the old behavior.
            if (isset($result['pageTsConfig']['TCEFORM.'][$tableName . '.'][$fieldName . '.']['PAGE_TSCONFIG_ID'])) {
                $pageTsConfig['flexHack.']['PAGE_TSCONFIG_ID'] = $result['pageTsConfig']['TCEFORM.'][$tableName . '.'][$fieldName . '.']['PAGE_TSCONFIG_ID'];
            }
            if (isset($result['pageTsConfig']['TCEFORM.'][$tableName . '.'][$fieldName . '.']['PAGE_TSCONFIG_IDLIST'])) {
                $pageTsConfig['flexHack.']['PAGE_TSCONFIG_IDLIST'] = $result['pageTsConfig']['TCEFORM.'][$tableName . '.'][$fieldName . '.']['PAGE_TSCONFIG_IDLIST'];
            }
            if (isset($result['pageTsConfig']['TCEFORM.'][$tableName . '.'][$fieldName . '.']['PAGE_TSCONFIG_STR'])) {
                $pageTsConfig['flexHack.']['PAGE_TSCONFIG_STR'] = $result['pageTsConfig']['TCEFORM.'][$tableName . '.'][$fieldName . '.']['PAGE_TSCONFIG_STR'];
            }

            // List of "new" tca fields that have no value within the flexform, yet. Those will be compiled in one go later.
            $tcaNewColumns = [];
            // List of "edit" tca fields that have a value in flexform, already. Those will be compiled in one go later.
            $tcaEditColumns = [];
            // Contains the data values for the "edit" tca fields.
            $tcaValueArray = [
                'uid' => $result['databaseRow']['uid'],
            ];
            foreach ($dataStructureSheetElements as $dataStructureSheetElementName => $dataStructureSheetElementDefinition) {
                if (isset($dataStructureSheetElementDefinition['type']) && $dataStructureSheetElementDefinition['type'] === 'array'
                    && isset($dataStructureSheetElementDefinition['section']) && (string)$dataStructureSheetElementDefinition['section'] === '1'
                ) {
                    // A section

                    // Existing section container elements
                    if (isset($dataValues['data'][$dataStructureSheetName]['lDEF'][$dataStructureSheetElementName]['el'])
                        && is_array($dataValues['data'][$dataStructureSheetName]['lDEF'][$dataStructureSheetElementName]['el'])
                    ) {
                        $containerArray = $dataValues['data'][$dataStructureSheetName]['lDEF'][$dataStructureSheetElementName]['el'];
                        foreach ($containerArray as $aContainerNumber => $aContainerArray) {
                            if (is_array($aContainerArray)) {
                                foreach ($aContainerArray as $aContainerName => $aContainerElementArray) {
                                    if ($aContainerName === '_TOGGLE') {
                                        // Don't handle internal toggle state field
                                        continue;
                                    }
                                    if (!isset($dataStructureSheetElements[$dataStructureSheetElementName]['el'][$aContainerName])) {
                                        // Container not defined in ds
                                        continue;
                                    }

                                    $newColumns = [];
                                    $editColumns = [];
                                    $valueArray = [
                                        'uid' => $result['databaseRow']['uid'],
                                    ];
                                    foreach ($dataStructureSheetElements[$dataStructureSheetElementName]['el'][$aContainerName]['el'] as $singleFieldName => $singleFieldConfiguration) {
                                        // $singleFieldValueArray = ['data']['sSections']['lDEF']['section_1']['el']['1']['container_1']['el']['element_1']
                                        $singleFieldValueArray = [];
                                        if (isset($aContainerElementArray['el'][$singleFieldName])
                                            && is_array($aContainerElementArray['el'][$singleFieldName])
                                        ) {
                                            $singleFieldValueArray = $aContainerElementArray['el'][$singleFieldName];
                                        }

                                        if (array_key_exists('vDEF', $singleFieldValueArray)) {
                                            $editColumns[$singleFieldName] = $singleFieldConfiguration;
                                            $valueArray[$singleFieldName] = $singleFieldValueArray['vDEF'];
                                        } else {
                                            $newColumns[$singleFieldName] = $singleFieldConfiguration;
                                        }
                                    }

                                    $inputToFlexFormSegment = [
                                        'tableName' => $result['tableName'],
                                        'command' => '',
                                        // It is currently not possible to have pageTsConfig for section container
                                        'pageTsConfig' => [],
                                        'databaseRow' => $valueArray,
                                        'processedTca' => [
                                            'ctrl' => [],
                                            'columns' => [],
                                        ],
                                        'flexParentDatabaseRow' => $result['databaseRow'],
                                    ];

                                    if (!empty($newColumns)) {
                                        $inputToFlexFormSegment['command'] = 'new';
                                        $inputToFlexFormSegment['processedTca']['columns'] = $newColumns;
                                        $flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);

                                        foreach ($newColumns as $singleFieldName => $_) {
                                            // Set data value result
                                            if (array_key_exists($singleFieldName, $flexSegmentResult['databaseRow'])) {
                                                $result['databaseRow'][$fieldName]
                                                ['data'][$dataStructureSheetName]['lDEF'][$dataStructureSheetElementName]
                                                ['el'][$aContainerNumber][$aContainerName]['el'][$singleFieldName]['vDEF'] =
                                                    $flexSegmentResult['databaseRow'][$singleFieldName];
                                            }
                                            // Set TCA structure result, actually, this call *might* be obsolete since the "dummy"
                                            // handling below will set it again.
                                            $result['processedTca']['columns'][$fieldName]['config']['ds']
                                            ['sheets'][$dataStructureSheetName]['ROOT']['el']
                                            [$dataStructureSheetElementName]['el'][$aContainerName]['el'][$singleFieldName] =
                                                $flexSegmentResult['processedTca']['columns'][$singleFieldName];
                                        }
                                    }

                                    if (!empty($editColumns)) {
                                        $inputToFlexFormSegment['command'] = 'edit';
                                        $inputToFlexFormSegment['processedTca']['columns'] = $editColumns;
                                        $flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);

                                        foreach ($editColumns as $singleFieldName => $_) {
                                            // Set data value result
                                            if (array_key_exists($singleFieldName, $flexSegmentResult['databaseRow'])) {
                                                $result['databaseRow'][$fieldName]
                                                ['data'][$dataStructureSheetName]['lDEF'][$dataStructureSheetElementName]
                                                ['el'][$aContainerNumber][$aContainerName]['el'][$singleFieldName]['vDEF'] =
                                                    $flexSegmentResult['databaseRow'][$singleFieldName];
                                            }
                                            // Set TCA structure result, actually, this call *might* be obsolete since the "dummy"
                                            // handling below will set it again.
                                            $result['processedTca']['columns'][$fieldName]['config']['ds']
                                            ['sheets'][$dataStructureSheetName]['ROOT']['el']
                                            [$dataStructureSheetElementName]['el'][$aContainerName]['el'][$singleFieldName] =
                                                $flexSegmentResult['processedTca']['columns'][$singleFieldName];
                                        }
                                    }
                                }
                            }
                        }
                        // End of existing data value handling
                    } else {
                        // Force the section to be an empty array if there are no existing containers
                        $result['databaseRow'][$fieldName]
                        ['data'][$dataStructureSheetName]['lDEF'][$dataStructureSheetElementName]['el'] = [];
                    }

                    // Prepare "fresh" row for every possible container
                    if (isset($dataStructureSheetElements[$dataStructureSheetElementName]['el']) && is_array($dataStructureSheetElements[$dataStructureSheetElementName]['el'])) {
                        foreach ($dataStructureSheetElements[$dataStructureSheetElementName]['el'] as $possibleContainerName => $possibleContainerConfiguration) {
                            if (isset($possibleContainerConfiguration['el']) && is_array($possibleContainerConfiguration['el'])) {
                                // Initialize result data array templateRows
                                $result['databaseRow'][$fieldName]
                                ['data'][$dataStructureSheetName]['lDEF'][$dataStructureSheetElementName]['templateRows']
                                [$possibleContainerName]['el']
                                    = [];
                                foreach ($possibleContainerConfiguration['el'] as $singleFieldName => $singleFieldConfiguration) {

                                    // Nesting type=inline in container sections is not supported. Throw an exception if configured.
                                    if (isset($singleFieldConfiguration['config']['type']) && $singleFieldConfiguration['config']['type'] === 'inline') {
                                        throw new \UnexpectedValueException(
                                            'Invalid flex form data structure on field name "' . $fieldName . '" with element "' . $singleFieldName . '"'
                                                . ' in section container "' . $possibleContainerName . '": Nesting inline elements in flex form'
                                                . ' sections is not allowed.',
                                            1458745468
                                        );
                                    }

                                    // Nesting sections is not supported. Throw an exception if configured.
                                    if (is_array($singleFieldConfiguration)
                                        && isset($singleFieldConfiguration['type']) && $singleFieldConfiguration['type'] === 'array'
                                        && isset($singleFieldConfiguration['section']) && (string)$singleFieldConfiguration['section'] === '1'
                                    ) {
                                        throw new \UnexpectedValueException(
                                            'Invalid flex form data structure on field name "' . $fieldName . '" with element "' . $singleFieldName . '"'
                                                . ' in section container "' . $possibleContainerName . '": Nesting sections in container elements'
                                                . ' sections is not allowed.',
                                            1458745712
                                        );
                                    }

                                    $inputToFlexFormSegment = [
                                        'tableName' => $result['tableName'],
                                        'command' => 'new',
                                        'pageTsConfig' => [],
                                        'databaseRow' => [
                                            'uid' => $result['databaseRow']['uid'],
                                        ],
                                        'processedTca' => [
                                            'ctrl' => [],
                                            'columns' => [
                                                $singleFieldName => $singleFieldConfiguration,
                                            ],
                                        ],
                                        'flexParentDatabaseRow' => $result['databaseRow'],
                                    ];
                                    $flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);
                                    if (array_key_exists($singleFieldName, $flexSegmentResult['databaseRow'])) {
                                        $result['databaseRow'][$fieldName]
                                        ['data'][$dataStructureSheetName]['lDEF'][$dataStructureSheetElementName]['templateRows']
                                        [$possibleContainerName]['el'][$singleFieldName]['vDEF']
                                         = $flexSegmentResult['databaseRow'][$singleFieldName];
                                    }
                                    $result['processedTca']['columns'][$fieldName]['config']['ds']
                                    ['sheets'][$dataStructureSheetName]['ROOT']['el'][$dataStructureSheetElementName]['el']
                                    [$possibleContainerName]['el'][$singleFieldName]
                                        = $flexSegmentResult['processedTca']['columns'][$singleFieldName];
                                }
                            }
                        }
                    } // End of preparation for each possible container

                // type without section is not ok
                } elseif (isset($dataStructureSheetElementDefinition['type']) || isset($dataStructureSheetElementDefinition['section'])) {
                    throw new \UnexpectedValueException(
                        'Broken data structure on field name ' . $fieldName . '. section without type or vice versa is not allowed',
                        1440685208
                    );

                // A "normal" TCA element
                } else {
                    if (isset($dataValues['data'][$dataStructureSheetName]['lDEF'][$dataStructureSheetElementName])
                        && array_key_exists('vDEF', $dataValues['data'][$dataStructureSheetName]['lDEF'][$dataStructureSheetElementName])
                    ) {
                        $tcaEditColumns[$dataStructureSheetElementName] = $dataStructureSheetElementDefinition;
                        $tcaValueArray[$dataStructureSheetElementName] = $dataValues['data'][$dataStructureSheetName]['lDEF'][$dataStructureSheetElementName]['vDEF'];
                    } else {
                        $tcaNewColumns[$dataStructureSheetElementName] = $dataStructureSheetElementDefinition;
                    }
                } // End of single element handling
            }

            // process the tca columns for the current sheet
            $inputToFlexFormSegment = [
                // tablename of "parent" is given down for inline elements to resolve correctly
                'tableName' => $result['tableName'],
                'command' => '',
                'pageTsConfig' => $pageTsConfig,
                'databaseRow' => $tcaValueArray,
                'processedTca' => [
                    'ctrl' => [],
                    'columns' => [],
                ],
                'flexParentDatabaseRow' => $result['databaseRow'],
            ];

            if (!empty($tcaNewColumns)) {
                $inputToFlexFormSegment['command'] = 'new';
                $inputToFlexFormSegment['processedTca']['columns'] = $tcaNewColumns;
                $flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);

                foreach ($tcaNewColumns as $dataStructureSheetElementName => $_) {
                    // Set data value result
                    if (array_key_exists($dataStructureSheetElementName, $flexSegmentResult['databaseRow'])) {
                        $result['databaseRow'][$fieldName]
                        ['data'][$dataStructureSheetName]['lDEF'][$dataStructureSheetElementName]['vDEF']
                            = $flexSegmentResult['databaseRow'][$dataStructureSheetElementName];
                    }
                    // Set TCA structure result
                    $result['processedTca']['columns'][$fieldName]['config']['ds']
                    ['sheets'][$dataStructureSheetName]['ROOT']['el'][$dataStructureSheetElementName]
                        = $flexSegmentResult['processedTca']['columns'][$dataStructureSheetElementName];
                }
            }

            if (!empty($tcaEditColumns)) {
                $inputToFlexFormSegment['command'] = 'edit';
                $inputToFlexFormSegment['processedTca']['columns'] = $tcaEditColumns;
                $flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);

                foreach ($tcaEditColumns as $dataStructureSheetElementName => $_) {
                    // Set data value result
                    if (array_key_exists($dataStructureSheetElementName, $flexSegmentResult['databaseRow'])) {
                        $result['databaseRow'][$fieldName]
                        ['data'][$dataStructureSheetName]['lDEF'][$dataStructureSheetElementName]['vDEF']
                            = $flexSegmentResult['databaseRow'][$dataStructureSheetElementName];
                    }
                    // Set TCA structure result
                    $result['processedTca']['columns'][$fieldName]['config']['ds']
                    ['sheets'][$dataStructureSheetName]['ROOT']['el'][$dataStructureSheetElementName]
                        = $flexSegmentResult['processedTca']['columns'][$dataStructureSheetElementName];
                }
            }
        }

        return $result;
    }

    /**
     * Modify data structure of a single "sheet"
     * Sets "secondary" data like sheet names and so on, but does NOT modify single elements
     *
     * @param array $dataStructure Given data structure
     * @param array $pageTsOfSheet Page Ts config of given field
     * @return array Modified data structure
     */
    protected function modifySingleSheetInformation(array $dataStructure, array $pageTsOfSheet)
    {
        // Return if no elements defined
        if (!isset($dataStructure['ROOT']['el']) || !is_array($dataStructure['ROOT']['el'])) {
            return $dataStructure;
        }

        // Rename sheet (tab)
        if (!empty($pageTsOfSheet['sheetTitle'])) {
            $dataStructure['ROOT']['sheetTitle'] = $pageTsOfSheet['sheetTitle'];
        }
        // Set sheet description (tab)
        if (!empty($pageTsOfSheet['sheetDescription'])) {
            $dataStructure['ROOT']['sheetDescription'] = $pageTsOfSheet['sheetDescription'];
        }
        // Set sheet short description (tab)
        if (!empty($pageTsOfSheet['sheetShortDescr'])) {
            $dataStructure['ROOT']['sheetShortDescr'] = $pageTsOfSheet['sheetShortDescr'];
        }

        return $dataStructure;
    }

    /**
     * Add fields and values used by ds_pointerField to the meta data array so they can be used in AJAX context during rendering.
     *
     * @todo: This method is a stopgap measure to get required information into the AJAX controller
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @return array
     * @internal
     */
    protected function addDataStructurePointersToMetaData(array $result, $fieldName)
    {
        if (empty($result['processedTca']['columns'][$fieldName]['config']['ds_pointerField'])) {
            return $result;
        }

        $pointerFields = GeneralUtility::trimExplode(
            ',',
            $result['processedTca']['columns'][$fieldName]['config']['ds_pointerField']
        );
        $dsPointers = [
            $pointerFields[0] => !empty($result['databaseRow'][$pointerFields[0]]) ? $result['databaseRow'][$pointerFields[0]] : ''
        ];

        if (!empty($pointerFields[1])) {
            $dsPointers[$pointerFields[1]] =
                !empty($result['databaseRow'][$pointerFields[1]]) ? $result['databaseRow'][$pointerFields[1]] : '';
        }
        $result['processedTca']['columns'][$fieldName]['config']['ds']['meta']['dataStructurePointers'] = $dsPointers;
        return $result;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
