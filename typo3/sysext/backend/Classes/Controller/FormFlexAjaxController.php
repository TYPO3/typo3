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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Form\Behavior\UpdateValueOnFieldChange;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Page\JavaScriptItems;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Handle FormEngine flex field ajax calls
 */
#[AsController]
readonly class FormFlexAjaxController extends AbstractFormEngineAjaxController
{
    public function __construct(
        private FormDataCompiler $formDataCompiler,
        private NodeFactory $nodeFactory,
    ) {}

    /**
     * Render a single flex form section container to add it to the DOM
     */
    public function containerAdd(ServerRequestInterface $request): ResponseInterface
    {
        $queryParameters = $request->getParsedBody();

        $vanillaUid = (int)$queryParameters['vanillaUid'];
        $databaseRowUid = $queryParameters['databaseRowUid'];
        $command = $queryParameters['command'];
        $tableName = $queryParameters['tableName'];
        $fieldName = $queryParameters['fieldName'];
        $recordTypeValue = $queryParameters['recordTypeValue'];
        $flexFormSheetName = $queryParameters['flexFormSheetName'];
        $flexFormFieldName = $queryParameters['flexFormFieldName'];
        $flexFormContainerName = $queryParameters['flexFormContainerName'];

        // Prepare TCA and data values for a new section container using data providers
        // @todo Replace with a mutable schema
        $processedTca = $GLOBALS['TCA'][$tableName];
        // Get a new unique id for this container.
        $flexFormContainerIdentifier = StringUtility::getUniqueId();
        $flexSectionContainerPreparation = [
            'flexFormSheetName' => $flexFormSheetName,
            'flexFormFieldName' => $flexFormFieldName,
            'flexFormContainerName' => $flexFormContainerName,
            'flexFormContainerIdentifier' => $flexFormContainerIdentifier,
        ];

        $formDataCompilerInput = [
            'request' => $request,
            'tableName' => $tableName,
            'vanillaUid' => (int)$vanillaUid,
            'command' => $command,
            'recordTypeValue' => $recordTypeValue,
            'processedTca' => $processedTca,
            'flexSectionContainerPreparation' => $flexSectionContainerPreparation,
        ];
        // A new container on a new record needs the 'NEW123' uid here, see comment
        // in DatabaseUniqueUidNewRow for more information on that.
        // @todo: Resolve, maybe with a redefinition of vanillaUid to transport the information more clean through this var?
        // @see issue #80100 for a series of changes in this area
        if ($command === 'new') {
            $formDataCompilerInput['databaseRow']['uid'] = $databaseRowUid;
        }
        $formData = $this->formDataCompiler->compile($formDataCompilerInput, GeneralUtility::makeInstance(TcaDatabaseRecord::class));

        $dataStructure = $formData['processedTca']['columns'][$fieldName]['config']['ds'];
        $dataStructureIdentifier = $formData['processedTca']['columns'][$fieldName]['config']['dataStructureIdentifier'];
        $formData['fieldName'] = $fieldName;
        $formData['flexFormDataStructureArray'] = $dataStructure['sheets'][$flexFormSheetName]['ROOT']['el'][$flexFormFieldName]['children'][$flexFormContainerIdentifier];
        $formData['flexFormDataStructureIdentifier'] = $dataStructureIdentifier;
        $formData['flexFormFieldName'] = $flexFormFieldName;
        $formData['flexFormSheetName'] = $flexFormSheetName;
        $formData['flexFormContainerName'] = $flexFormContainerName;
        $formData['flexFormContainerIdentifier'] = $flexFormContainerIdentifier;

        $formData['flexFormFormPrefix'] = '[data][' . $flexFormSheetName . '][lDEF][' . $flexFormFieldName . '][el]';

        // Set initialized data of that section container from compiler to the array part used
        // by flexFormElementContainer which prepares parameterArray. Important for initialized
        // values of group element.
        if (isset($formData['databaseRow'][$fieldName]
                ['data'][$flexFormSheetName]
                ['lDEF'][$flexFormFieldName]
                ['el'][$flexFormContainerIdentifier][$flexFormContainerName]['el']
        )
            && is_array(
                $formData['databaseRow'][$fieldName]
                ['data'][$flexFormSheetName]
                ['lDEF'][$flexFormFieldName]
                ['el'][$flexFormContainerIdentifier][$flexFormContainerName]['el']
            )
        ) {
            $formData['flexFormRowData'] = $formData['databaseRow'][$fieldName]
                ['data'][$flexFormSheetName]
                ['lDEF'][$flexFormFieldName]
                ['el'][$flexFormContainerIdentifier][$flexFormContainerName]['el'];
        }

        $formData['parameterArray']['itemFormElName'] = 'data[' . $tableName . '][' . $formData['databaseRow']['uid'] . '][' . $fieldName . ']';

        // Client-side behavior for event handlers:
        $formData['parameterArray']['fieldChangeFunc'] = [];
        $formData['parameterArray']['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = new UpdateValueOnFieldChange(
            $tableName,
            (string)$formData['databaseRow']['uid'],
            $fieldName,
            $formData['parameterArray']['itemFormElName']
        );

        // @todo: check GroupElement for usage of elementBaseName ... maybe kick that thing?

        // Feed resulting form data to container structure to render HTML and other result data
        $formData['renderType'] = 'flexFormContainerContainer';
        $newContainerResult = $this->nodeFactory->create($formData)->render();
        $scriptItems = new JavaScriptItems();

        $jsonResult = [
            'html' => $newContainerResult['html'],
            'stylesheetFiles' => [],
            'scriptItems' => $scriptItems,
        ];

        foreach ($newContainerResult['stylesheetFiles'] as $stylesheetFile) {
            $jsonResult['stylesheetFiles'][] = $this->getRelativePathToStylesheetFile($stylesheetFile);
        }
        if (!empty($newContainerResult['additionalInlineLanguageLabelFiles'])) {
            $labels = [];
            foreach ($newContainerResult['additionalInlineLanguageLabelFiles'] as $additionalInlineLanguageLabelFile) {
                ArrayUtility::mergeRecursiveWithOverrule(
                    $labels,
                    $this->getLabelsFromLocalizationFile($additionalInlineLanguageLabelFile)
                );
            }
            $scriptItems->addGlobalAssignment(['TYPO3' => ['lang' => $labels]]);
        }
        $this->addJavaScriptModulesToJavaScriptItems($newContainerResult['javaScriptModules'] ?? [], $scriptItems);

        return new JsonResponse($jsonResult);
    }
}
