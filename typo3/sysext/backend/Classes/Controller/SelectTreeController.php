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
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend controller for selectTree ajax operations
 */
class SelectTreeController
{
    /**
     * Returns json representing category tree
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function fetchDataAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $tableName = $request->getQueryParams()['table'];
        $fieldName = $request->getQueryParams()['field'];

        // Prepare processedTca: Remove all column definitions except the one that contains
        // our tree definition. This way only this field is calculated, everything else is ignored.
        if (!isset($GLOBALS['TCA'][$tableName])  || !is_array($GLOBALS['TCA'][$tableName])) {
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
        $recordTypeValue = $request->getQueryParams()['record_type_value'];
        $processedTca['types'][$recordTypeValue]['showitem'] = $fieldName;
        // Unset all columns except our field
        $processedTca['columns'] = [
            $fieldName => $processedTca['columns'][$fieldName],
        ];

        $flexFormPath = [];
        if ($processedTca['columns'][$fieldName]['config']['type'] === 'flex') {
            $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
            $dataStructureIdentifier = json_encode($request->getQueryParams()['flex_form_datastructure_identifier']);
            $dataStructure = $flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
            // Try to reduce given data structure down to the relevant element only
            $flexFormPath = $request->getQueryParams()['flex_form_path'];
            $fieldPattern = 'data[' . $tableName . '][';
            $flexFormPath = str_replace($fieldPattern, '', $flexFormPath);
            $flexFormPath = substr($flexFormPath, 0, -1);
            $flexFormPath = explode('][', $flexFormPath);
            if (isset($dataStructure['sheets'][$flexFormPath[3]]['ROOT']['el'][$flexFormPath[5]])) {
                $dataStructure = [
                    'sheets' => [
                        $flexFormPath[3] => [
                            'ROOT' => [
                                'type' => 'array',
                                'el' => [
                                    $flexFormPath[5] => $dataStructure['sheets'][$flexFormPath[3]]['ROOT']['el'][$flexFormPath[5]],
                                ],
                            ],
                        ],
                    ],
                ];
            }
            $processedTca['columns'][$fieldName]['config']['ds'] = $dataStructure;
            $processedTca['columns'][$fieldName]['config']['dataStructureIdentifier'] = $dataStructureIdentifier;
        }

        $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'tableName' => $request->getQueryParams()['table'],
            'vanillaUid' => (int)$request->getQueryParams()['uid'],
            'command' => $request->getQueryParams()['command'],
            'processedTca' => $processedTca,
            'recordTypeValue' => $recordTypeValue,
            'selectTreeCompileItems' => true,
        ];
        $formData = $formDataCompiler->compile($formDataCompilerInput);

        if ($formData['processedTca']['columns'][$fieldName]['config']['type'] === 'flex') {
            $treeData = $formData['processedTca']['columns'][$fieldName]['config']['ds']
                ['sheets'][$flexFormPath[3]]['ROOT']['el'][$flexFormPath[5]]['config']['items'];
        } else {
            $treeData = $formData['processedTca']['columns'][$fieldName]['config']['items'];
        }

        $json = json_encode($treeData);
        $response->getBody()->write($json);
        return $response;
    }
}
