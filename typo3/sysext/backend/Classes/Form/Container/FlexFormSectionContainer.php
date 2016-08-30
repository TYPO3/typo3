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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Handle flex form sections.
 *
 * This container is created by FlexFormElementContainer if a "single" element is in
 * fact a sections. For each existing section container it creates as FlexFormContainerContainer
 * to render its inner fields, additionally for each possible container a "template" of this
 * container type is rendered and added - to be added by JS to DOM on click on "new xy container".
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
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $languageService = $this->getLanguageService();

        $flexFormFieldsArray = $this->data['flexFormDataStructureArray'];
        $flexFormRowData = $this->data['flexFormRowData'];
        $flexFormFieldIdentifierPrefix = $this->data['flexFormFieldIdentifierPrefix'];
        $flexFormSectionType = $this->data['flexFormSectionType'];
        $flexFormSectionTitle = $this->data['flexFormSectionTitle'];

        $userHasAccessToDefaultLanguage = $this->getBackendUserAuthentication()->checkLanguageAccess(0);

        $resultArray = $this->initializeResultArray();

        // Creating IDs for form fields:
        // It's important that the IDs "cascade" - otherwise we can't dynamically expand the flex form
        // because this relies on simple string substitution of the first parts of the id values.
        $flexFormFieldIdentifierPrefix = $flexFormFieldIdentifierPrefix . '-' . GeneralUtility::shortMd5(uniqid('id', true));

        // Render each existing container
        foreach ($flexFormRowData as $flexFormContainerCounter => $existingSectionContainerData) {
            // @todo: This relies on the fact that "_TOGGLE" is *below* the real data in the saved xml structure
            if (is_array($existingSectionContainerData)) {
                $existingSectionContainerDataStructureType = key($existingSectionContainerData);
                $existingSectionContainerData = $existingSectionContainerData[$existingSectionContainerDataStructureType];
                $containerDataStructure = $flexFormFieldsArray[$existingSectionContainerDataStructureType];
                // There may be cases where a field is still in DB but does not exist in definition
                if (is_array($containerDataStructure)) {
                    $sectionTitle = '';
                    if (!empty(trim($containerDataStructure['title']))) {
                        $sectionTitle = $languageService->sL(trim($containerDataStructure['title']));
                    }

                    $options = $this->data;
                    $options['flexFormRowData'] = $existingSectionContainerData['el'];
                    $options['flexFormDataStructureArray'] = $containerDataStructure['el'];
                    $options['flexFormFieldIdentifierPrefix'] = $flexFormFieldIdentifierPrefix;
                    $options['flexFormFormPrefix'] = $this->data['flexFormFormPrefix'] . '[' . $flexFormSectionType . ']' . '[el]';
                    $options['flexFormContainerName'] = $existingSectionContainerDataStructureType;
                    $options['flexFormContainerCounter'] = $flexFormContainerCounter;
                    $options['flexFormContainerTitle'] = $sectionTitle;
                    $options['flexFormContainerElementCollapsed'] = (bool)$existingSectionContainerData['el']['_TOGGLE'];
                    $options['renderType'] = 'flexFormContainerContainer';
                    $flexFormContainerContainerResult = $this->nodeFactory->create($options)->render();
                    $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $flexFormContainerContainerResult);
                }
            }
        }

        // "New container" handling: Creates a "template" of each possible container and stuffs it
        // somewhere into DOM to be handled with JS magic.
        // Fun part: Handle the fact that such things may be set for children
        $containerTemplatesHtml = [];
        foreach ($flexFormFieldsArray as $flexFormContainerName => $flexFormFieldDefinition) {
            $containerTemplateHtml = [];
            $sectionTitle = '';
            if (!empty(trim($flexFormFieldDefinition['title']))) {
                $sectionTitle = $languageService->sL(trim($flexFormFieldDefinition['title']));
            }

            $options = $this->data;
            // @todo: this should use the prepared templateRow parallel to the single elements to have support of default values!
            $options['flexFormRowData'] = [];
            $options['flexFormDataStructureArray'] = $flexFormFieldDefinition['el'];
            $options['flexFormFieldIdentifierPrefix'] = $flexFormFieldIdentifierPrefix;
            $options['flexFormFormPrefix'] = $this->data['flexFormFormPrefix'] . '[' . $flexFormSectionType . ']' . '[el]';
            $options['flexFormContainerName'] = $flexFormContainerName;
            $options['flexFormContainerCounter'] = $flexFormFieldIdentifierPrefix . '-form';
            $options['flexFormContainerTitle'] = $sectionTitle;
            $options['flexFormContainerElementCollapsed'] = false;
            $options['renderType'] = 'flexFormContainerContainer';
            $flexFormContainerContainerTemplateResult = $this->nodeFactory->create($options)->render();

            // Extract the random identifier used by the ExtJS tree. This is used later on in the onClick handler
            // to dynamically modify the javascript code and instanciate a unique ExtJS tree instance per section.
            $treeElementIdentifier = '';
            if (!empty($flexFormContainerContainerTemplateResult['extJSCODE'])) {
                if (preg_match('/StandardTreeItemData\["([a-f0-9]{32})"\]/', $flexFormContainerContainerTemplateResult['extJSCODE'], $matches)) {
                    $treeElementIdentifier = $matches[1];
                }
            }

            $uniqueId = StringUtility::getUniqueId('idvar');
            $identifierPrefixJs = 'replace(/' . $flexFormFieldIdentifierPrefix . '-/g,"' . $flexFormFieldIdentifierPrefix . '-"+' . $uniqueId . '+"-")';
            $identifierPrefixJs .= '.replace(/(tceforms-(datetime|date)field-)/g,"$1" + (new Date()).getTime())';

            if (!empty($treeElementIdentifier)) {
                $identifierPrefixJs .= '.replace(/(tree_?)?' . $treeElementIdentifier . '/g,"$1" + (' . $uniqueId . '))';
            }

            $onClickInsert = [];
            $onClickInsert[] = 'var ' . $uniqueId . ' = "' . 'idx"+(new Date()).getTime();';
            $onClickInsert[] = 'TYPO3.jQuery("#' . $flexFormFieldIdentifierPrefix . '").append(TYPO3.jQuery(' . json_encode($flexFormContainerContainerTemplateResult['html']) . '.' . $identifierPrefixJs . '));';
            $onClickInsert[] = 'TYPO3.jQuery("#' . $flexFormFieldIdentifierPrefix . '").t3FormEngineFlexFormElement();';
            $onClickInsert[] = 'eval(unescape("' . rawurlencode(implode(';', $flexFormContainerContainerTemplateResult['additionalJavaScriptPost'])) . '").' . $identifierPrefixJs . ');';
            if (!empty($treeElementIdentifier)) {
                $onClickInsert[] = 'eval(unescape("' . rawurlencode($flexFormContainerContainerTemplateResult['extJSCODE']) . '").' . $identifierPrefixJs . ');';
            }
            $onClickInsert[] = 'TBE_EDITOR.addActionChecks("submit", unescape("' . rawurlencode(implode(';', $flexFormContainerContainerTemplateResult['additionalJavaScriptSubmit'])) . '").' . $identifierPrefixJs . ');';
            $onClickInsert[] = 'TYPO3.FormEngine.reinitialize();';
            $onClickInsert[] = 'TYPO3.FormEngine.Validation.initializeInputFields();';
            $onClickInsert[] = 'TYPO3.FormEngine.Validation.validate();';
            $onClickInsert[] = 'return false;';

            $containerTemplateHtml[] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars(implode(LF, $onClickInsert)) . '">';
            $containerTemplateHtml[] =    $iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render();
            $containerTemplateHtml[] =    htmlspecialchars(GeneralUtility::fixed_lgd_cs($sectionTitle, 30));
            $containerTemplateHtml[] = '</a>';
            $containerTemplatesHtml[] = implode(LF, $containerTemplateHtml);

            $flexFormContainerContainerTemplateResult['html'] = '';
            $flexFormContainerContainerTemplateResult['additionalJavaScriptPost'] = [];
            $flexFormContainerContainerTemplateResult['additionalJavaScriptSubmit'] = [];

            $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $flexFormContainerContainerTemplateResult);
        }

        // Create new elements links
        $createElementsHtml = [];
        if ($userHasAccessToDefaultLanguage) {
            $createElementsHtml[] = '<div class="t3-form-field-add-flexsection">';
            $createElementsHtml[] =    '<div class="btn-group">';
            $createElementsHtml[] =        implode('|', $containerTemplatesHtml);
            $createElementsHtml[] =    '</div>';
            $createElementsHtml[] = '</div>';
        }

        // Wrap child stuff
        $toggleAll = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.toggleall', true);
        $html = [];
        $html[] = '<div class="panel panel-tab">';
        $html[] =     '<div class="panel-body">';
        $html[] =         '<div class="t3-form-field-container t3-form-flex">';
        $html[] =             '<div class="t3-form-field-label-flexsection">';
        $html[] =                 '<h4>';
        $html[] =                     htmlspecialchars($flexFormSectionTitle);
        $html[] =                 '</h4>';
        $html[] =             '</div>';
        $html[] =             '<div class="t3js-form-field-toggle-flexsection t3-form-flexsection-toggle">';
        $html[] =                 '<a class="btn btn-default" href="#" title="' . $toggleAll . '">';
        $html[] =                     $iconFactory->getIcon('actions-move-right', Icon::SIZE_SMALL)->render() . $toggleAll;
        $html[] =                 '</a>';
        $html[] =             '</div>';
        $html[] =             '<div';
        $html[] =                 'id="' . $flexFormFieldIdentifierPrefix . '"';
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
