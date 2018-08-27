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
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowLoopException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowRootException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidPointerFieldValueException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Migrations\TcaMigration;
use TYPO3\CMS\Core\Preparations\TcaPreparation;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resolve flex data structure and data values, prepare and normalize.
 *
 * This is the first data provider in the chain of flex form related providers.
 */
class TcaFlexPrepare implements FormDataProviderInterface
{
    /**
     * Resolve flex data structures and prepare flex data values.
     *
     * Normalize some details to have aligned array nesting for the rest of the
     * processing method and the render engine.
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
            $result = $this->removeTceFormsArrayKeyFromDataStructureElements($result, $fieldName);
            $result = $this->migrateFlexformTcaDataStructureElements($result, $fieldName);
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
        if (!isset($result['processedTca']['columns'][$fieldName]['config']['dataStructureIdentifier'])) {
            $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);

            $dataStructureArray = ['sheets' => ['sDEF' => []]];

            try {
                $dataStructureIdentifier = $flexFormTools->getDataStructureIdentifier(
                    $result['processedTca']['columns'][$fieldName],
                    $result['tableName'],
                    $fieldName,
                    $result['databaseRow']
                );
                $dataStructureArray = $flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
            } catch (InvalidParentRowException $e) {
            } catch (InvalidParentRowLoopException $e) {
            } catch (InvalidParentRowRootException $e) {
            } catch (InvalidPointerFieldValueException $e) {
            } catch (InvalidIdentifierException $e) {
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
     * Remove "TCEforms" key from all elements in data structure to simplify further parsing.
     *
     * Example config:
     * ['config']['ds']['sheets']['sDEF']['ROOT']['el']['anElement']['TCEforms']['label'] becomes
     * ['config']['ds']['sheets']['sDEF']['ROOT']['el']['anElement']['label']
     *
     * @param array $result Result array
     * @param string $fieldName Currently handled field name
     * @return array Modified result
     */
    protected function removeTceFormsArrayKeyFromDataStructureElements(array $result, $fieldName)
    {
        $modifiedDataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
        $modifiedDataStructure = $this->removeElementTceFormsRecursive($modifiedDataStructure);
        $result['processedTca']['columns'][$fieldName]['config']['ds'] = $modifiedDataStructure;
        return $result;
    }

    /**
     * Moves ['el']['something']['TCEforms'] to ['el']['something'] and ['ROOT']['TCEforms'] to ['ROOT'] recursive
     *
     * @param array $structure Given hierarchy
     * @return array Modified hierarchy
     */
    protected function removeElementTceFormsRecursive(array $structure)
    {
        $newStructure = [];
        foreach ($structure as $key => $value) {
            if ($key === 'ROOT' && is_array($value) && isset($value['TCEforms'])) {
                $value = array_merge($value, $value['TCEforms']);
                unset($value['TCEforms']);
            }
            if ($key === 'el' && is_array($value)) {
                $newSubStructure = [];
                foreach ($value as $subKey => $subValue) {
                    if (is_array($subValue) && count($subValue) === 1 && isset($subValue['TCEforms'])) {
                        $newSubStructure[$subKey] = $subValue['TCEforms'];
                    } else {
                        $newSubStructure[$subKey] = $subValue;
                    }
                }
                $value = $newSubStructure;
            }
            if (is_array($value)) {
                $value = $this->removeElementTceFormsRecursive($value);
            }
            $newStructure[$key] = $value;
        }
        return $newStructure;
    }

    /**
     * On-the-fly migration for flex form "TCA"
     *
     * @param array $result Result array
     * @param string $fieldName Currently handled field name
     * @return array Modified result
     */
    protected function migrateFlexformTcaDataStructureElements(array $result, $fieldName)
    {
        $modifiedDataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
        $modifiedDataStructure = $this->migrateFlexformTcaRecursive($modifiedDataStructure, $result['tableName'], $fieldName);
        $result['processedTca']['columns'][$fieldName]['config']['ds'] = $modifiedDataStructure;
        return $result;
    }

    /**
     * Recursively migrate flex form TCA
     *
     * @param array $structure Given hierarchy
     * @param string $table
     * @param string $fieldName
     * @return array Modified hierarchy
     */
    protected function migrateFlexformTcaRecursive($structure, $table, $fieldName)
    {
        $newStructure = [];
        foreach ($structure as $key => $value) {
            if ($key === 'el' && is_array($value)) {
                $newSubStructure = [];
                $tcaMigration = GeneralUtility::makeInstance(TcaMigration::class);
                $tcaPreparation = GeneralUtility::makeInstance(TcaPreparation::class);
                foreach ($value as $subKey => $subValue) {
                    // On-the-fly migration for flex form "TCA"
                    // @deprecated since TYPO3 CMS 7. Not removed in TYPO3 CMS 8 though. This call will stay for now to allow further TCA migrations in 8.
                    $dummyTca = [
                        'dummyTable' => [
                            'columns' => [
                                'dummyField' => $subValue,
                            ],
                        ],
                    ];
                    $migratedTca = $tcaMigration->migrate($dummyTca);
                    $messages = $tcaMigration->getMessages();
                    if (!empty($messages)) {
                        $context = 'FormEngine did an on-the-fly migration of a flex form data structure. This is deprecated and will be removed.'
                            . ' Merge the following changes into the flex form definition of table "' . $table . '"" in field "' . $fieldName . '"":';
                        array_unshift($messages, $context);
                        trigger_error(implode(LF, $messages), E_USER_DEPRECATED);
                    }
                    $preparedTca = $tcaPreparation->prepare($migratedTca);
                    $newSubStructure[$subKey] = $preparedTca['dummyTable']['columns']['dummyField'];
                }
                $value = $newSubStructure;
            }
            if (is_array($value)) {
                $value = $this->migrateFlexformTcaRecursive($value, $table, $fieldName);
            }
            $newStructure[$key] = $value;
        }
        return $newStructure;
    }
}
