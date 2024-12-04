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

namespace TYPO3\CMS\Backend\Form\Container;

use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handle flex form sections.
 *
 * This container is created by FlexFormElementContainer if a "single" element is in
 * fact a section. For each existing section container it creates as FlexFormContainerContainer
 * to render its inner fields.
 * Additionally, a button for each possible container is rendered with information for the
 * ajax controller that fetches one on click.
 */
class FlexFormSectionContainer extends AbstractContainer
{
    public function __construct(
        private readonly IconFactory $iconFactory,
    ) {}

    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $languageService = $this->getLanguageService();

        $flexFormDataStructureIdentifier = $this->data['flexFormDataStructureIdentifier'];
        $flexFormDataStructureArray = $this->data['flexFormDataStructureArray'];
        $flexFormRowData = $this->data['flexFormRowData'];
        $flexFormFieldName = $this->data['flexFormFieldName'];
        $flexFormSheetName = $this->data['flexFormSheetName'];

        $userHasAccessToDefaultLanguage = $this->getBackendUserAuthentication()->checkLanguageAccess(0);

        $resultArray = $this->initializeResultArray();

        // Render each existing container
        foreach ($flexFormDataStructureArray['children'] as $flexFormContainerIdentifier => $containerDataStructure) {
            $existingContainerData = $flexFormRowData[$flexFormContainerIdentifier];
            $existingSectionContainerDataStructureType = key($existingContainerData);
            $existingContainerData = $existingContainerData[$existingSectionContainerDataStructureType];
            $options = $this->data;
            $options['flexFormRowData'] = $existingContainerData['el'];
            $options['flexFormDataStructureArray'] = $containerDataStructure;
            $options['flexFormFormPrefix'] = $this->data['flexFormFormPrefix'] . '[' . $flexFormFieldName . '][el]';
            $options['flexFormContainerName'] = $existingSectionContainerDataStructureType;
            $options['flexFormContainerIdentifier'] = $flexFormContainerIdentifier;
            $options['renderType'] = 'flexFormContainerContainer';
            $flexFormContainerContainerResult = $this->nodeFactory->create($options)->render();
            $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $flexFormContainerContainerResult);
        }

        $containerId = sprintf('flexform-section-container-%s-%s-%s-%s', $flexFormSheetName, $this->data['fieldName'], $flexFormFieldName, md5($this->data['elementBaseName']));
        $sectionContainerId = sprintf('flexform-section-%s-%s-%s-%s', $flexFormSheetName, $this->data['fieldName'], $flexFormFieldName, md5($this->data['elementBaseName']));
        $hashedSectionContainerId = 'section-' . md5($sectionContainerId);

        // "New container" handling: Creates buttons for each possible container with all relevant information for the ajax call.
        $containerTemplatesHtml = [];
        foreach ($flexFormDataStructureArray['el'] as $flexFormContainerName => $flexFormFieldDefinition) {
            $containerTitle = '';
            if (!empty(trim($flexFormFieldDefinition['title']))) {
                $containerTitle = $languageService->sL(trim($flexFormFieldDefinition['title']));
            }
            $containerTemplateHtml = [];
            $containerTemplateHtml[] = '<a';
            $containerTemplateHtml[] =     'href="#"';
            $containerTemplateHtml[] =     'class="btn btn-default t3js-flex-container-add"';
            $containerTemplateHtml[] =     'data-vanillauid="' . (int)$this->data['vanillaUid'] . '"';
            // no int cast for databaseRow uid, this can be "NEW1234..."
            $containerTemplateHtml[] =     'data-databaserowuid="' . htmlspecialchars($this->data['databaseRow']['uid']) . '"';
            $containerTemplateHtml[] =     'data-command="' . htmlspecialchars($this->data['command']) . '"';
            $containerTemplateHtml[] =     'data-tablename="' . htmlspecialchars($this->data['tableName']) . '"';
            $containerTemplateHtml[] =     'data-fieldname="' . htmlspecialchars($this->data['fieldName']) . '"';
            $containerTemplateHtml[] =     'data-recordtypevalue="' . $this->data['recordTypeValue'] . '"';
            $containerTemplateHtml[] =     'data-datastructureidentifier="' . htmlspecialchars($flexFormDataStructureIdentifier) . '"';
            $containerTemplateHtml[] =     'data-flexformsheetname="' . htmlspecialchars($flexFormSheetName) . '"';
            $containerTemplateHtml[] =     'data-flexformfieldname="' . htmlspecialchars($flexFormFieldName) . '"';
            $containerTemplateHtml[] =     'data-flexformcontainername="' . htmlspecialchars($flexFormContainerName) . '"';
            $containerTemplateHtml[] =     'data-target="#' . htmlspecialchars($hashedSectionContainerId) . '"';
            $containerTemplateHtml[] = '>';
            $containerTemplateHtml[] =    $this->iconFactory->getIcon('actions-document-new', IconSize::SMALL)->render();
            $containerTemplateHtml[] =    htmlspecialchars(GeneralUtility::fixed_lgd_cs($containerTitle, 30));
            $containerTemplateHtml[] = '</a>';
            $containerTemplatesHtml[] = implode(LF, $containerTemplateHtml);
        }
        // Create new elements links
        $createElementsHtml = [];
        if ($userHasAccessToDefaultLanguage) {
            $createElementsHtml[] = '<div class="t3-form-field-add-flexsection">';
            $createElementsHtml[] =    '<div class="btn-group">';
            $createElementsHtml[] =        implode('', $containerTemplatesHtml);
            $createElementsHtml[] =    '</div>';
            $createElementsHtml[] = '</div>';
        }

        $sectionTitle = '';
        if (!empty(trim($flexFormDataStructureArray['title'] ?? ''))) {
            $sectionTitle = $languageService->sL(trim($flexFormDataStructureArray['title']));
        }

        // Wrap child stuff
        $toggleAll = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.toggleall'));
        $html = [];
        $html[] = '<div class="form-section">';
        $html[] =     '<div class="t3-form-field-container t3-form-flex" id="' . htmlspecialchars($containerId) . '" data-section="#' . htmlspecialchars($hashedSectionContainerId) . '">';
        $html[] =       '<fieldset>';
        $html[] =           '<legend class="form-label t3js-formengine-label">';
        $html[] =                 htmlspecialchars($sectionTitle);
        $html[] =           '</legend>';
        $html[] =           '<div class="form-group">';
        $html[] =               '<button class="btn btn-default t3-form-flexsection-toggle" type="button" title="' . $toggleAll . '" data-expand-all="false">';
        $html[] =                   $this->iconFactory->getIcon('actions-move-right', IconSize::SMALL)->render() . $toggleAll;
        $html[] =               '</button>';
        $html[] =           '</div>';
        $html[] =           '<div';
        $html[] =               'id="' . htmlspecialchars($hashedSectionContainerId) . '"';
        $html[] =               'class="panel-group panel-hover t3-form-field-container-flexsection t3-flex-container"';
        $html[] =               'data-t3-flex-allow-restructure="' . ($userHasAccessToDefaultLanguage ? '1' : '0') . '"';
        $html[] =           '>';
        $html[] =               $resultArray['html'];
        $html[] =           '</div>';
        $html[] =           implode(LF, $createElementsHtml);
        $html[] =       '</fieldset>';
        $html[] =     '</div>';
        $html[] = '</div>';

        $resultArray['html'] = implode(LF, $html);
        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create(
            '@typo3/backend/form-engine/container/flex-form-section-container.js'
        )->instance($containerId);

        return $resultArray;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
