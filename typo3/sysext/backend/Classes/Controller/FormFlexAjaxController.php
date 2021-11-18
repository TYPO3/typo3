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
use TYPO3\CMS\Backend\Form\Behavior\UpdateValueOnFieldChange;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Page\JavaScriptItems;
use TYPO3\CMS\Core\Utility\ArrayUtility;
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
     * @return ResponseInterface
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
            // This is a hack to handle creation of flex form section containers on new / not-yet-persisted
            // records that use "sub types" - for example tt_content ctype plugin with plugin list_type (eg. news_pi1):
            // The container needs to know the list_type to create the proper flex form section container.
            // We *can* fetch the given sub type from the dataStructureIdentifier when it's type is 'tca'.
            // This is hacky since we're using 'internal' knowledge of the dataStructureIdentifier here, which
            // *should* be avoided. But sub types should vanish from TCA at some point anyway (this usage shows
            // the complexity they introduce quite well), so we live with the solution for now instead of handing
            // the selected sub type through the system differently.
            $subtypeValueField = $processedTca['types'][$recordTypeValue]['subtype_value_field'] ?? null;
            $subtypeValue = explode(',', $queryParameters['dataStructureIdentifier']['dataStructureKey'] ?? '')[0];
            if ($subtypeValueField
                && $subtypeValue
                && ($queryParameters['dataStructureIdentifier']['type'] ?? '') === 'tca'
                && !in_array($subtypeValue, ['*', 'list', 'default'], true)
            ) {
                // Set selected sub type to init flex form container creation for this type & sub type combination
                $formDataCompilerInput['databaseRow'][$subtypeValueField] = $subtypeValue;
            }
        }
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
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $formData['renderType'] = 'flexFormContainerContainer';
        $newContainerResult = $nodeFactory->create($formData)->render();
        $scriptItems = GeneralUtility::makeInstance(JavaScriptItems::class);

        $jsonResult = [
            'html' => $newContainerResult['html'],
            'stylesheetFiles' => [],
            'scriptItems' => $scriptItems,
            'scriptCall' => [],
        ];

        // @todo deprecate with TYPO3 v12.0
        foreach ($newContainerResult['additionalJavaScriptPost'] as $singleAdditionalJavaScriptPost) {
            $jsonResult['scriptCall'][] = $singleAdditionalJavaScriptPost;
        }
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
        $this->addRegisteredRequireJsModulesToJavaScriptItems($newContainerResult, $scriptItems);
        // @todo deprecate modules with arbitrary JavaScript callback function in TYPO3 v12.0
        $jsonResult['scriptCall'] = $this->createExecutableStringRepresentationOfRegisteredRequireJsModules($newContainerResult, true);

        return new JsonResponse($jsonResult);
    }
}
