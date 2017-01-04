<?php
declare(strict_types=1);
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
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Handle FormEngine flex field ajax calls
 */
class FormFlexAjaxController extends AbstractFormEngineAjaxController
{
    /**
     * Render a single flex form section container to add it to the DOM
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function containerAdd(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParameters = $request->getParsedBody();

        $vanillaUid = (int)$queryParameters['vanillaUid'];
        $databaseRowUid = $queryParameters['databaseRowUid'];
        $command = $queryParameters['command'];
        $tableName = $queryParameters['tableName'];
        $fieldName = $queryParameters['fieldName'];
        $recordTypeValue = $queryParameters['recordTypeValue'];
        $dataStructureIdentifier = json_encode($queryParameters['dataStructureIdentifier']);
        $flexFormSheetName = $queryParameters['flexFormSheetName'];
        $flexFormFieldName = $queryParameters['flexFormFieldName'];
        $flexFormContainerName = $queryParameters['flexFormContainerName'];

        // Prepare TCA and data values for a new section container using data providers
        $processedTca = $GLOBALS['TCA'][$tableName];
        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
        $dataStructure = $flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
        $processedTca['columns'][$fieldName]['config']['ds'] = $dataStructure;
        $processedTca['columns'][$fieldName]['config']['dataStructureIdentifier'] = $dataStructureIdentifier;
        // Get a new unique id for this container.
        $flexFormContainerIdentifier = StringUtility::getUniqueId();
        $flexSectionContainerPreparation = [
            'flexFormSheetName' => $flexFormSheetName,
            'flexFormFieldName' => $flexFormFieldName,
            'flexFormContainerName' => $flexFormContainerName,
            'flexFormContainerIdentifier' => $flexFormContainerIdentifier,
        ];

        $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'tableName' => $tableName,
            'vanillaUid' => (int)$vanillaUid,
            'databaseRow' => [
                'uid' => $databaseRowUid,
            ],
            'command' => $command,
            'recordTypeValue' => $recordTypeValue,
            'processedTca' => $processedTca,
            'flexSectionContainerPreparation' => $flexSectionContainerPreparation,
        ];
        $formData = $formDataCompiler->compile($formDataCompilerInput);

        $dataStructure = $formData['processedTca']['columns'][$fieldName]['config']['ds'];
        $formData['fieldName'] = $fieldName;
        $formData['flexFormDataStructureArray'] = $dataStructure['sheets'][$flexFormSheetName]['ROOT']['el'][$flexFormFieldName]['children'][$flexFormContainerIdentifier];
        $formData['flexFormDataStructureIdentifier'] = $dataStructureIdentifier;
        $formData['flexFormFieldName'] = $flexFormFieldName;
        $formData['flexFormSheetName'] = $flexFormSheetName;
        $formData['flexFormContainerName'] = $flexFormContainerName;
        $formData['flexFormContainerIdentifier'] = $flexFormContainerIdentifier;
        $formData['flexFormContainerElementCollapsed'] = false;

        $formData['flexFormFormPrefix'] = '[data][' . $flexFormSheetName . '][lDEF]' . '[' . $flexFormFieldName . ']' . '[el]';
        $formData['parameterArray']['itemFormElName'] = 'data[' . $tableName . '][' . $formData['databaseRow']['uid'] . '][' . $fieldName . ']';

        // JavaScript code for event handlers:
        // @todo: see if we can get rid of this - used in group elements, and also for the "reload" on type field changes
        $formData['parameterArray']['fieldChangeFunc'] = [];
        $formData['parameterArray']['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = 'TBE_EDITOR.fieldChanged('
            . GeneralUtility::quoteJSvalue($tableName)
            . ',' . GeneralUtility::quoteJSvalue($formData['databaseRow']['uid'])
            . ',' . GeneralUtility::quoteJSvalue($fieldName)
            . ',' . GeneralUtility::quoteJSvalue($formData['parameterArray']['itemFormElName'])
            . ');';

        // @todo: check GroupElement for usage of elementBaseName ... maybe kick that thing?

        // Feed resulting form data to container structure to render HTML and other result data
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $formData['renderType'] = 'flexFormContainerContainer';
        $newContainerResult = $nodeFactory->create($formData)->render();

        $jsonResult = [
            'html' => $newContainerResult['html'],
            'scriptCall' => [],
        ];

        if (!empty($newContainerResult['additionalJavaScriptSubmit'])) {
            $additionalJavaScriptSubmit = implode('', $newContainerResult['additionalJavaScriptSubmit']);
            $additionalJavaScriptSubmit = str_replace([CR, LF], '', $additionalJavaScriptSubmit);
            $jsonResult['scriptCall'][] = 'TBE_EDITOR.addActionChecks("submit", "' . addslashes($additionalJavaScriptSubmit) . '");';
        }
        foreach ($newContainerResult['additionalJavaScriptPost'] as $singleAdditionalJavaScriptPost) {
            $jsonResult['scriptCall'][] = $singleAdditionalJavaScriptPost;
        }
        // @todo: handle stylesheetFiles, additionalInlineLanguageLabelFiles

        $requireJsModule = $this->createExecutableStringRepresentationOfRegisteredRequireJsModules($newContainerResult);
        $jsonResult['scriptCall'] = array_merge($requireJsModule, $jsonResult['scriptCall']);

        $response->getBody()->write(json_encode($jsonResult));

        return $response;
    }
}
