<?php
namespace TYPO3\CMS\Backend\Form\Container;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
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
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
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
            // @todo: This relies on the fact that "_TOGGLE" is *below* the real data in the saved xml structure
            $existingSectionContainerDataStructureType = key($existingContainerData);
            $existingContainerData = $existingContainerData[$existingSectionContainerDataStructureType];
            $options = $this->data;
            $options['flexFormRowData'] = $existingContainerData['el'];
            $options['flexFormDataStructureArray'] = $containerDataStructure;
            $options['flexFormFormPrefix'] = $this->data['flexFormFormPrefix'] . '[' . $flexFormFieldName . ']' . '[el]';
            $options['flexFormContainerName'] = $existingSectionContainerDataStructureType;
            $options['flexFormContainerIdentifier'] = $flexFormContainerIdentifier;
            $options['flexFormContainerElementCollapsed'] = (bool)$flexFormRowData[$flexFormContainerIdentifier]['_TOGGLE'];
            $options['renderType'] = 'flexFormContainerContainer';
            $flexFormContainerContainerResult = $this->nodeFactory->create($options)->render();
            $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $flexFormContainerContainerResult);
        }

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
            $containerTemplateHtml[] = '>';
            $containerTemplateHtml[] =    $iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render();
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
        if (!empty(trim($flexFormDataStructureArray['title']))) {
            $sectionTitle = $languageService->sL(trim($flexFormDataStructureArray['title']));
        }

        // Wrap child stuff
        $toggleAll = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.toggleall'));
        $html = [];
        $html[] = '<div class="panel panel-tab">';
        $html[] =     '<div class="panel-body">';
        $html[] =         '<div class="t3-form-field-container t3-form-flex">';
        $html[] =             '<div class="t3-form-field-label-flexsection">';
        $html[] =                 '<h4>';
        $html[] =                     htmlspecialchars($sectionTitle);
        $html[] =                 '</h4>';
        $html[] =             '</div>';
        $html[] =             '<div class="t3js-form-field-toggle-flexsection t3-form-flexsection-toggle">';
        $html[] =                 '<a class="btn btn-default" href="#" title="' . $toggleAll . '">';
        $html[] =                     $iconFactory->getIcon('actions-move-right', Icon::SIZE_SMALL)->render() . $toggleAll;
        $html[] =                 '</a>';
        $html[] =             '</div>';
        $html[] =             '<div';
        $html[] =                 'class="panel-group panel-hover t3-form-field-container-flexsection t3-flex-container"';
        $html[] =                 'data-t3-flex-allow-restructure="' . ($userHasAccessToDefaultLanguage ? '1' : '0') . '"';
        $html[] =             '>';
        $html[] =                 $resultArray['html'];
        $html[] =             '</div>';
        $html[] =             implode(LF, $createElementsHtml);
        $html[] =         '</div>';
        $html[] =     '</div>';
        $html[] = '</div>';

        $resultArray['html'] = implode(LF, $html);

        return $resultArray;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
