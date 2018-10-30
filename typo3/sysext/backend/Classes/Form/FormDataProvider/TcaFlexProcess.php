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
     * Determine available languages and sanitize ds for further processing. Then kick
     * and validate further details like excluded fields. Finally for each possible
     * value and ds, call FormDataCompiler with set FlexFormSegment group to resolve
     * single field stuff like item processor functions.
     *
     * @param array $result
     * @throws \RuntimeException
     * @return array
     */
    public function addData(array $result)
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'flex') {
                continue;
            }
            if (!isset($result['processedTca']['columns'][$fieldName]['config']['dataStructureIdentifier'])) {
                throw new \RuntimeException(
                    'Data structure identifier must be set, typically by executing TcaFlexPrepare data provider before',
                    1480765571
                );
            }
            $this->scanForInvalidSectionContainerTca($result, $fieldName);
            $dataStructureIdentifier = $result['processedTca']['columns'][$fieldName]['config']['dataStructureIdentifier'];
            $simpleDataStructureIdentifier = $this->getSimplifiedDataStructureIdentifier($dataStructureIdentifier);
            $pageTsConfigOfFlex = $this->getPageTsOfFlex($result, $fieldName, $simpleDataStructureIdentifier);
            $result = $this->modifyOuterDataStructure($result, $fieldName, $pageTsConfigOfFlex);
            $result = $this->removeExcludeFieldsFromDataStructure($result, $fieldName, $simpleDataStructureIdentifier);
            $result = $this->removeDisabledFieldsFromDataStructure($result, $fieldName, $pageTsConfigOfFlex);
            // A "normal" call opening a record: Process data structure and field values
            // This is called for "new" container ajax request too, since display conditions from section container
            // elements can access record values of other flex form sheets and we need their values then.
            $result = $this->modifyDataStructureAndDataValuesByFlexFormSegmentGroup($result, $fieldName, $pageTsConfigOfFlex);
            if (!empty($result['flexSectionContainerPreparation'])) {
                // Create data and default values for a new section container, set by FormFlexAjaxController
                $result = $this->prepareNewSectionContainer($result, $fieldName);
            }
        }

        return $result;
    }

    /**
     * Some TCA combinations like inline or nesting a section into a section container is not
     * supported and throws exceptions.
     *
     * @param array $result Result array
     * @param string $fieldName Handled field name
     * @throws \UnexpectedValueException
     */
    protected function scanForInvalidSectionContainerTca(array $result, string $fieldName)
    {
        $dataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
        if (!isset($dataStructure['sheets']) || !is_array($dataStructure['sheets'])) {
            return;
        }
        foreach ($dataStructure['sheets'] as $dataStructureSheetName => $dataStructureSheetDefinition) {
            if (!isset($dataStructureSheetDefinition['ROOT']['el']) || !is_array($dataStructureSheetDefinition['ROOT']['el'])) {
                continue;
            }
            $dataStructureFields = $dataStructureSheetDefinition['ROOT']['el'];
            foreach ($dataStructureFields as $dataStructureFieldName => $dataStructureFieldDefinition) {
                if (isset($dataStructureFieldDefinition['type']) && $dataStructureFieldDefinition['type'] === 'array'
                    && isset($dataStructureFieldDefinition['section']) && (string)$dataStructureFieldDefinition['section'] === '1'
                ) {
                    if (isset($dataStructureFieldDefinition['el']) && is_array($dataStructureFieldDefinition['el'])) {
                        foreach ($dataStructureFieldDefinition['el'] as $containerName => $containerConfiguration) {
                            if (isset($containerConfiguration['el']) && is_array($containerConfiguration['el'])) {
                                foreach ($containerConfiguration['el'] as $singleFieldName => $singleFieldConfiguration) {
                                    // Nesting type=inline in container sections is not supported. Throw an exception if configured.
                                    if (isset($singleFieldConfiguration['config']['type']) && $singleFieldConfiguration['config']['type'] === 'inline') {
                                        throw new \UnexpectedValueException(
                                            'Invalid flex form data structure on field name "' . $fieldName . '" with element "' . $singleFieldName . '"'
                                            . ' in section container "' . $containerName . '": Nesting inline elements in flex form'
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
                                            . ' in section container "' . $containerName . '": Nesting sections in container elements'
                                            . ' sections is not allowed.',
                                            1458745712
                                        );
                                    }

                                    // Nesting type="select" and type="group" within section containers is not supported,
                                    // the data storage can not deal with that and in general it is not supported to add a
                                    // named reference to the anonymous section container structure.
                                    if (is_array($singleFieldConfiguration)
                                        && isset($singleFieldConfiguration['config']['type'])
                                        && ($singleFieldConfiguration['config']['type'] === 'group' || $singleFieldConfiguration['config']['type'] === 'select')
                                        && array_key_exists('MM', $singleFieldConfiguration['config'])
                                    ) {
                                        throw new \UnexpectedValueException(
                                            'Invalid flex form data structure on field name "' . $fieldName . '" with element "' . $singleFieldName . '"'
                                            . ' in section container "' . $containerName . '": Nesting select and group elements in flex form'
                                            . ' sections is not allowed with MM relations.',
                                            1481647089
                                        );
                                    }
                                }
                            }
                        }
                    }
                } elseif (isset($dataStructureFieldDefinition['type']) xor isset($dataStructureFieldDefinition['section'])) {
                    // type without section is not ok
                    throw new \UnexpectedValueException(
                        'Broken data structure on field name ' . $fieldName . '. section without type or vice versa is not allowed',
                        1440685208
                    );
                }
            }
        }
    }

    /**
     * Calculate a simplified (and wrong) data structure identifier.
     * This is used to find pageTsConfig options of flex fields and exclude field definitions later, see methods below.
     * If the data structure identifier is not type=tca based and if dataStructureKey is not as expected, fallback is "default"
     *
     * Example pi_flexform with ext:news in tt_content:
     * * TCA config of pi_flexform ds_pointerfield is set to "list_type,CType"
     * * list_type in databaseRow is "news_pi1"
     * * CType in databaseRow is "list"
     * * The resulting dataStructureIdentifier calculated by FlexFormTools is then:
     *   {"type":"tca","tableName":"tt_content","fieldName":"pi_flexform","dataStructureKey":"news_pi1,list"}
     * * The resulting simpleDataStructureIdentifier is "news_pi1"
     * * The pageTsConfig base path used for flex field overrides is "TCEFORM.tt_content.pi_flexform.news_pi1", a full
     *   example path disabling a field: "TCEFORM.tt_content.pi_flexform.news_pi1.sDEF.settings\.orderBy.disabled = 1"
     * * The exclude path for be_user exclude rights is "tt_content:pi_flexform;news_pi1", a full example:
     *   tt_content:pi_flexform;news_pi1;sDEF;settings.orderBy
     *
     * Notes:
     * This approach is obviously limited. It is not possible to override flex form DS via pageTsConfig for other complex
     * or dynamically created data structure definitions. And worse, the fallback to "default" may lead to naming clashes
     * if two different data structures have identical sheet and field names.
     * Also, the exclude field handling is limited and it is not possible to respect 'exclude' fields in flex form
     * data structures if the dataStructureIdentifier is based on type="record" or manipulated by a hook in FlexFormTools.
     * All that can only be solved by changing the pageTsConfig syntax referencing flex fields, probably by involving the whole
     * data structure identifier and going away from this "simple" approach. For exclude fields there is the additional
     * issue that the special="exclude" code is based on guess work, to find possible data structures. If this area here is
     * changed and a pageTsConfig syntax change is raised, it would probably be a good idea to solve the access restrictions
     * area at the same time - see the related methods that deal with flex field handling for special="exclude" for
     * more comments on this.
     * Another limitation is that the current syntax in both pageTsConfig and exclude fields does not
     * consider flex form section containers at all.
     *
     * @param string $dataStructureIdentifier
     * @return string
     */
    protected function getSimplifiedDataStructureIdentifier(string $dataStructureIdentifier): string
    {
        $identifierArray = json_decode($dataStructureIdentifier, true);
        $simpleDataStructureIdentifier = 'default';
        if (isset($identifierArray['type']) && $identifierArray['type'] === 'tca' && isset($identifierArray['dataStructureKey'])) {
            $explodedKey = explode(',', $identifierArray['dataStructureKey']);
            if (!empty($explodedKey[1]) && $explodedKey[1] !== 'list' && $explodedKey[1] !== '*') {
                $simpleDataStructureIdentifier = $explodedKey[1];
            } elseif (!empty($explodedKey[0]) && $explodedKey[0] !== 'list' && $explodedKey[0] !== '*') {
                $simpleDataStructureIdentifier = $explodedKey[0];
            }
        }
        return $simpleDataStructureIdentifier;
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
     * Two main parts in this method:
     * * Process values and TCA of existing section containers
     * * Process TCA of "normal" fields
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

        $formDataGroup = GeneralUtility::makeInstance(FlexFormSegment::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);

        foreach ($dataStructure['sheets'] as $dataStructureSheetName => $dataStructureSheetDefinition) {
            if (!isset($dataStructureSheetDefinition['ROOT']['el']) || !is_array($dataStructureSheetDefinition['ROOT']['el'])) {
                continue;
            }
            $dataStructureFields = $dataStructureSheetDefinition['ROOT']['el'];

            // Prepare pageTsConfig of this sheet
            $pageTsConfig['TCEFORM.'][$tableName . '.'] = [];
            if (isset($pageTsConfig[$dataStructureSheetName . '.']) && is_array($pageTsConfig[$dataStructureSheetName . '.'])) {
                $pageTsConfig['TCEFORM.'][$tableName . '.'] = $pageTsConfig[$dataStructureSheetName . '.'];
            }

            // List of "new" tca fields that have no value within the flexform, yet. Those will be compiled in one go later.
            $tcaNewColumns = [];
            // List of "edit" tca fields that have a value in flexform, already. Those will be compiled in one go later.
            $tcaEditColumns = [];
            // Contains the data values for the "edit" tca fields.
            $tcaValueArray = [
                'uid' => $result['databaseRow']['uid'],
            ];
            foreach ($dataStructureFields as $dataStructureFieldName => $dataStructureFieldDefinition) {
                if (isset($dataStructureFieldDefinition['type']) && $dataStructureFieldDefinition['type'] === 'array'
                    && isset($dataStructureFieldDefinition['section']) && (string)$dataStructureFieldDefinition['section'] === '1'
                ) {
                    // Existing section containers. Prepare data values and create a unique data structure per container.
                    // This is important for instance for display conditions later enabling them to change ds per container instance.
                    // In the end, the data values in
                    // ['databaseRow']['aFieldName']['data']['aSheet']['lDEF']['aSectionField']['el']['aContainer']
                    // are prepared, and additionally, the processedTca data structure is changed and has a specific container
                    // name per container instance in
                    // ['processedTca']['columns']['aFieldName']['config']['ds']['sheets']['aSheet']['ROOT']['el']['aSectionField']['children']['aContainer']
                    if (isset($dataValues['data'][$dataStructureSheetName]['lDEF'][$dataStructureFieldName]['el'])
                        && is_array($dataValues['data'][$dataStructureSheetName]['lDEF'][$dataStructureFieldName]['el'])
                    ) {
                        $containerValueArray = $dataValues['data'][$dataStructureSheetName]['lDEF'][$dataStructureFieldName]['el'];
                        $containerDataStructuresPerContainer = [];
                        foreach ($containerValueArray as $aContainerIdentifier => $aContainerArray) {
                            if (is_array($aContainerArray)) {
                                foreach ($aContainerArray as $aContainerName => $aContainerElementArray) {
                                    if ($aContainerName === '_TOGGLE') {
                                        // Don't handle internal toggle state field
                                        continue;
                                    }
                                    if (!isset($dataStructureFields[$dataStructureFieldName]['el'][$aContainerName])) {
                                        // Container not defined in ds
                                        continue;
                                    }
                                    $vanillaContainerDataStructure = $dataStructureFields[$dataStructureFieldName]['el'][$aContainerName];

                                    $newColumns = [];
                                    $editColumns = [];
                                    $valueArray = [
                                        'uid' => $result['databaseRow']['uid'],
                                    ];
                                    foreach ($vanillaContainerDataStructure['el'] as $singleFieldName => $singleFieldConfiguration) {
                                        // $singleFieldValueArray = ['data']['sSections']['lDEF']['section_1']['el']['1']['container_1']['el']['element_1']
                                        $singleFieldValueArray = [];
                                        if (isset($aContainerElementArray['el'][$singleFieldName])
                                            && is_array($aContainerElementArray['el'][$singleFieldName])
                                        ) {
                                            $singleFieldValueArray = $aContainerElementArray['el'][$singleFieldName];
                                        }

                                        if (array_key_exists('vDEF', $singleFieldValueArray)) {
                                            $valueArray[$singleFieldName] = $singleFieldValueArray['vDEF'];
                                        } else {
                                            $newColumns[$singleFieldName] = $singleFieldConfiguration;
                                        }
                                        $editColumns[$singleFieldName] = $singleFieldConfiguration;
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
                                        'selectTreeCompileItems' => $result['selectTreeCompileItems'],
                                        'flexParentDatabaseRow' => $result['databaseRow'],
                                        'effectivePid' => $result['effectivePid'],
                                    ];

                                    if (!empty($newColumns)) {
                                        // This is scenario "field has been added to data structure, but field value does not exist in value array yet"
                                        // We want that stuff like TCA "default" values are then applied to those fields. What we do here is
                                        // calling the data compiler with those "new" fields to fetch their values and set them in value array.
                                        // Those fields are then compiled a second time in the "edit" phase to prepare their final TCA.
                                        // This two-phase compiling is needed to ensure that for instance display conditions work with
                                        // fields that may just have been added to the data structure but are not yet initialized as data value.
                                        $inputToFlexFormSegment['command'] = 'new';
                                        $inputToFlexFormSegment['processedTca']['columns'] = $newColumns;
                                        $flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);
                                        foreach ($newColumns as $singleFieldName => $_) {
                                            // Set data value result to feed it to "edit" next
                                            $valueArray[$singleFieldName] = $flexSegmentResult['databaseRow'][$singleFieldName];
                                        }
                                    }

                                    if (!empty($editColumns)) {
                                        $inputToFlexFormSegment['command'] = 'edit';
                                        $inputToFlexFormSegment['processedTca']['columns'] = $editColumns;
                                        $flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);
                                        foreach ($editColumns as $singleFieldName => $_) {
                                            $result['databaseRow'][$fieldName]
                                                ['data'][$dataStructureSheetName]['lDEF'][$dataStructureFieldName]
                                                ['el'][$aContainerIdentifier][$aContainerName]['el'][$singleFieldName]['vDEF']
                                                = $flexSegmentResult['databaseRow'][$singleFieldName];
                                            $containerDataStructuresPerContainer[$aContainerIdentifier] = $vanillaContainerDataStructure;
                                            $containerDataStructuresPerContainer[$aContainerIdentifier]['el'] = $flexSegmentResult['processedTca']['columns'];
                                        }
                                    }
                                }
                            }
                        } // End of existing data value handling
                        // Set 'data structures per container' next to 'el' that contains vanilla data structures
                        $result['processedTca']['columns'][$fieldName]['config']['ds']
                            ['sheets'][$dataStructureSheetName]['ROOT']['el']
                            [$dataStructureFieldName]['children'] = $containerDataStructuresPerContainer;
                    } else {
                        // Force the section data array to be an empty array if there are no existing containers
                        $result['databaseRow'][$fieldName]
                            ['data'][$dataStructureSheetName]['lDEF'][$dataStructureFieldName]['el'] = [];
                        // Force data structure array to be empty if there are no existing containers
                        $result['processedTca']['columns'][$fieldName]['config']['ds']
                            ['sheets'][$dataStructureSheetName]['ROOT']['el']
                            [$dataStructureFieldName]['children'] = [];
                    }
                } else {
                    // A "normal" TCA flex form element, no section
                    if (isset($dataValues['data'][$dataStructureSheetName]['lDEF'][$dataStructureFieldName])
                        && array_key_exists('vDEF', $dataValues['data'][$dataStructureSheetName]['lDEF'][$dataStructureFieldName])
                    ) {
                        $tcaEditColumns[$dataStructureFieldName] = $dataStructureFieldDefinition;
                        $tcaValueArray[$dataStructureFieldName] = $dataValues['data'][$dataStructureSheetName]['lDEF'][$dataStructureFieldName]['vDEF'];
                    } else {
                        $tcaNewColumns[$dataStructureFieldName] = $dataStructureFieldDefinition;
                    }
                } // End of single element handling
            }

            // process the tca columns for the current sheet
            $inputToFlexFormSegment = [
                'tableName' => $result['tableName'],
                'command' => '',
                'pageTsConfig' => $pageTsConfig,
                'databaseRow' => $tcaValueArray,
                'processedTca' => [
                    'ctrl' => [],
                    'columns' => [],
                ],
                'flexParentDatabaseRow' => $result['databaseRow'],
                // Whether to compile TCA tree items - inherit from parent
                'selectTreeCompileItems' => $result['selectTreeCompileItems'],
                'effectivePid' => $result['effectivePid'],
            ];

            if (!empty($tcaNewColumns)) {
                // @todo: this has the same problem in scenario "a field was added later" as flex section container
                $inputToFlexFormSegment['command'] = 'new';
                $inputToFlexFormSegment['processedTca']['columns'] = $tcaNewColumns;
                $flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);

                foreach ($tcaNewColumns as $dataStructureFieldName => $_) {
                    // Set data value result
                    if (array_key_exists($dataStructureFieldName, $flexSegmentResult['databaseRow'])) {
                        $result['databaseRow'][$fieldName]
                            ['data'][$dataStructureSheetName]['lDEF'][$dataStructureFieldName]['vDEF']
                            = $flexSegmentResult['databaseRow'][$dataStructureFieldName];
                    }
                    // Set TCA structure result
                    $result['processedTca']['columns'][$fieldName]['config']['ds']
                        ['sheets'][$dataStructureSheetName]['ROOT']['el'][$dataStructureFieldName]
                        = $flexSegmentResult['processedTca']['columns'][$dataStructureFieldName];
                }
            }

            if (!empty($tcaEditColumns)) {
                $inputToFlexFormSegment['command'] = 'edit';
                $inputToFlexFormSegment['processedTca']['columns'] = $tcaEditColumns;
                $flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);

                foreach ($tcaEditColumns as $dataStructureFieldName => $_) {
                    // Set data value result
                    if (array_key_exists($dataStructureFieldName, $flexSegmentResult['databaseRow'])) {
                        $result['databaseRow'][$fieldName]
                            ['data'][$dataStructureSheetName]['lDEF'][$dataStructureFieldName]['vDEF']
                            = $flexSegmentResult['databaseRow'][$dataStructureFieldName];
                    }
                    // Set TCA structure result
                    $result['processedTca']['columns'][$fieldName]['config']['ds']
                        ['sheets'][$dataStructureSheetName]['ROOT']['el'][$dataStructureFieldName]
                        = $flexSegmentResult['processedTca']['columns'][$dataStructureFieldName];
                }
            }
        }

        return $result;
    }

    /**
     * Prepare data structure and data values for a new section container.
     *
     * @param array $result Incoming result array
     * @param string $fieldName The field name with this flex form
     * @return array Modified result
     */
    protected function prepareNewSectionContainer(array $result, string $fieldName): array
    {
        $flexSectionContainerPreparation = $result['flexSectionContainerPreparation'];
        $flexFormSheetName = $flexSectionContainerPreparation['flexFormSheetName'];
        $flexFormFieldName = $flexSectionContainerPreparation['flexFormFieldName'];
        $flexFormContainerName = $flexSectionContainerPreparation['flexFormContainerName'];
        $flexFormContainerIdentifier = $flexSectionContainerPreparation['flexFormContainerIdentifier'];

        $containerConfiguration = $result['processedTca']['columns'][$fieldName]['config']['ds']
            ['sheets'][$flexFormSheetName]['ROOT']['el'][$flexFormFieldName]['el'][$flexFormContainerName];

        if (isset($containerConfiguration['el']) && is_array($containerConfiguration['el'])) {
            $formDataGroup = GeneralUtility::makeInstance(FlexFormSegment::class);
            $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
            $inputToFlexFormSegment = [
                'tableName' => $result['tableName'],
                'command' => 'new',
                // It is currently not possible to have pageTsConfig for section container
                'pageTsConfig' => [],
                'databaseRow' => [
                    'uid' => $result['databaseRow']['uid'],
                ],
                'processedTca' => [
                    'ctrl' => [],
                    'columns' => $containerConfiguration['el'],
                ],
                'selectTreeCompileItems' => $result['selectTreeCompileItems'],
                'flexParentDatabaseRow' => $result['databaseRow'],
                'effectivePid' => $result['effectivePid'],
            ];
            $flexSegmentResult = $formDataCompiler->compile($inputToFlexFormSegment);

            foreach ($containerConfiguration['el'] as $singleFieldName => $singleFieldConfiguration) {
                // Set 'data structures for this new container' to 'children'
                $result['processedTca']['columns'][$fieldName]['config']['ds']
                    ['sheets'][$flexFormSheetName]['ROOT']['el']
                    [$flexFormFieldName]['children'][$flexFormContainerIdentifier]
                    = $containerConfiguration;
                $result['processedTca']['columns'][$fieldName]['config']['ds']
                    ['sheets'][$flexFormSheetName]['ROOT']['el']
                    [$flexFormFieldName]['children'][$flexFormContainerIdentifier]['el']
                    = $flexSegmentResult['processedTca']['columns'];
                // Set calculated value - this especially contains "default values from TCA"
                $result['databaseRow'][$fieldName]['data'][$flexFormSheetName]['lDEF']
                    [$flexFormFieldName]['el']
                    [$flexFormContainerIdentifier][$flexFormContainerName]['el'][$singleFieldName]['vDEF']
                    = $flexSegmentResult['databaseRow'][$singleFieldName];
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
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
