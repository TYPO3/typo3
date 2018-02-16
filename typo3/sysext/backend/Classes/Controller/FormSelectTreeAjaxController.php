<?php
namespace TYPO3\CMS\Backend\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaSelectTreeAjaxFieldData;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend controller for selectTree ajax operations
 */
class FormSelectTreeAjaxController
{
    /**
     * Returns json representing category tree
     *
     * @param ServerRequestInterface $request
     * @throws \RuntimeException
     * @return ResponseInterface
     */
    public function fetchDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $tableName = $request->getQueryParams()['tableName'];
        $fieldName = $request->getQueryParams()['fieldName'];

        // Prepare processedTca: Remove all column definitions except the one that contains
        // our tree definition. This way only this field is calculated, everything else is ignored.
        if (!isset($GLOBALS['TCA'][$tableName]) || !is_array($GLOBALS['TCA'][$tableName])) {
            throw new \RuntimeException(
                'TCA for table ' . $tableName . ' not found',
                1479386729
            );
        }
        $processedTca = $GLOBALS['TCA'][$tableName];
        if (!isset($processedTca['columns'][$fieldName]) || !is_array($processedTca['columns'][$fieldName])) {
            throw new \RuntimeException(
                'TCA for table ' . $tableName . ' and field ' . $fieldName . ' not found',
                1479386990
            );
        }

        // Force given record type and set showitem to our field only
        $recordTypeValue = $request->getQueryParams()['recordTypeValue'];
        $processedTca['types'][$recordTypeValue]['showitem'] = $fieldName;
        // Unset all columns except our field
        $processedTca['columns'] = [
            $fieldName => $processedTca['columns'][$fieldName],
        ];

        $dataStructureIdentifier = '';
        $flexFormSheetName = '';
        $flexFormFieldName = '';
        $flexFormContainerIdentifier = '';
        $flexFormContainerFieldName = '';
        $flexSectionContainerPreparation = [];
        if ($processedTca['columns'][$fieldName]['config']['type'] === 'flex') {
            if (!empty($request->getQueryParams()['dataStructureIdentifier'])) {
                $dataStructureIdentifier = json_encode($request->getQueryParams()['dataStructureIdentifier']);
            }
            $flexFormSheetName = $request->getQueryParams()['flexFormSheetName'];
            $flexFormFieldName = $request->getQueryParams()['flexFormFieldName'];
            $flexFormContainerName = $request->getQueryParams()['flexFormContainerName'];
            $flexFormContainerIdentifier = $request->getQueryParams()['flexFormContainerIdentifier'];
            $flexFormContainerFieldName = $request->getQueryParams()['flexFormContainerFieldName'];
            $flexFormSectionContainerIsNew = (bool)$request->getQueryParams()['flexFormSectionContainerIsNew'];

            $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
            $dataStructure = $flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);

            // Reduce given data structure down to the relevant element only
            if (empty($flexFormContainerFieldName)) {
                if (isset($dataStructure['sheets'][$flexFormSheetName]['ROOT']
                    ['el'][$flexFormFieldName])
                ) {
                    $dataStructure = [
                        'sheets' => [
                            $flexFormSheetName => [
                                'ROOT' => [
                                    'type' => 'array',
                                    'el' => [
                                        $flexFormFieldName => $dataStructure['sheets'][$flexFormSheetName]['ROOT']
                                            ['el'][$flexFormFieldName],
                                    ],
                                ],
                            ],
                        ],
                    ];
                }
            } else {
                if (isset($dataStructure['sheets'][$flexFormSheetName]['ROOT']
                    ['el'][$flexFormFieldName]
                    ['el'][$flexFormContainerName]
                    ['el'][$flexFormContainerFieldName])
                ) {
                    // If this is a tree in a section container that has just been added by the FlexFormAjaxController
                    // "new container" action, then this container is not yet persisted, so we need to trigger the
                    // TcaFlexProcess data provider again to prepare the DS and databaseRow of that container.
                    if ($flexFormSectionContainerIsNew) {
                        $flexSectionContainerPreparation = [
                            'flexFormSheetName' => $flexFormSheetName,
                            'flexFormFieldName' => $flexFormFieldName,
                            'flexFormContainerName' => $flexFormContainerName,
                            'flexFormContainerIdentifier' => $flexFormContainerIdentifier,
                        ];
                    }
                    // Now restrict the data structure to our tree element only
                    $dataStructure = [
                        'sheets' => [
                            $flexFormSheetName => [
                                'ROOT' => [
                                    'type' => 'array',
                                    'el' => [
                                        $flexFormFieldName => [
                                            'section' => 1,
                                            'type' => 'array',
                                            'el' => [
                                                $flexFormContainerName => [
                                                    'el' => [
                                                        $flexFormContainerFieldName => $dataStructure['sheets'][$flexFormSheetName]['ROOT']
                                                            ['el'][$flexFormFieldName]
                                                            ['el'][$flexFormContainerName]
                                                            ['el'][$flexFormContainerFieldName]
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ];
                }
            }
            $processedTca['columns'][$fieldName]['config']['ds'] = $dataStructure;
            $processedTca['columns'][$fieldName]['config']['dataStructureIdentifier'] = $dataStructureIdentifier;
        }

        $formDataGroup = GeneralUtility::makeInstance(TcaSelectTreeAjaxFieldData::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'tableName' => $tableName,
            'vanillaUid' => (int)$request->getQueryParams()['uid'],
            'command' => $request->getQueryParams()['command'],
            'processedTca' => $processedTca,
            'recordTypeValue' => $recordTypeValue,
            'selectTreeCompileItems' => true,
            'flexSectionContainerPreparation' => $flexSectionContainerPreparation,
        ];
        $formData = $formDataCompiler->compile($formDataCompilerInput);

        if ($formData['processedTca']['columns'][$fieldName]['config']['type'] === 'flex') {
            if (empty($flexFormContainerFieldName)) {
                $treeData = $formData['processedTca']['columns'][$fieldName]['config']['ds']
                    ['sheets'][$flexFormSheetName]['ROOT']
                    ['el'][$flexFormFieldName]['config']['items'];
            } else {
                $treeData = $formData['processedTca']['columns'][$fieldName]['config']['ds']
                    ['sheets'][$flexFormSheetName]['ROOT']
                    ['el'][$flexFormFieldName]
                    ['children'][$flexFormContainerIdentifier]
                    ['el'][$flexFormContainerFieldName]['config']['items'];
            }
        } else {
            $treeData = $formData['processedTca']['columns'][$fieldName]['config']['items'];
        }
        return (new JsonResponse())->setPayload($treeData);
    }
}
