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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Flex form container implementation
 * This one is called by FlexFormSectionContainer and renders HTML for a single container.
 * For processing of single elements FlexFormElementContainer is called
 */
class FlexFormContainerContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $languageService = $this->getLanguageService();

        $table = $this->data['tableName'];
        $row = $this->data['databaseRow'];
        $fieldName = $this->data['fieldName'];
        $flexFormFormPrefix = $this->data['flexFormFormPrefix'];
        $flexFormContainerElementCollapsed = $this->data['flexFormContainerElementCollapsed'];
        $flexFormDataStructureArray = $this->data['flexFormDataStructureArray'];
        $parameterArray = $this->data['parameterArray'];

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $flexFormContainerIdentifier = $this->data['flexFormContainerIdentifier'];
        $actionFieldName = '_ACTION_FLEX_FORM'
            . $parameterArray['itemFormElName']
            . $this->data['flexFormFormPrefix']
            . '[_ACTION]'
            . '[' . $flexFormContainerIdentifier . ']';
        $toggleFieldName = 'data[' . $table . '][' . $row['uid'] . '][' . $fieldName . ']'
            . $flexFormFormPrefix
            . '[' . $flexFormContainerIdentifier . ']'
            . '[_TOGGLE]';

        $moveAndDeleteContent = [];
        $userHasAccessToDefaultLanguage = $this->getBackendUserAuthentication()->checkLanguageAccess(0);
        if ($userHasAccessToDefaultLanguage) {
            $moveAndDeleteContent[] = '<button type="button" class="btn btn-default t3js-delete"><span title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:delete')) . '">' . $iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render() . '</span></button>';
            $moveAndDeleteContent[] = '<button type="button" class="btn btn-default t3js-sortable-handle sortableHandle"><span title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:sortable.dragmove')) . '">' . $iconFactory->getIcon('actions-move-move', Icon::SIZE_SMALL)->render() . '</span></button>';
        }

        $options = $this->data;
        // Append container specific stuff to field prefix
        $options['flexFormFormPrefix'] = $flexFormFormPrefix . '[' . $flexFormContainerIdentifier . '][' . $this->data['flexFormContainerName'] . '][el]';
        $options['flexFormDataStructureArray'] = $flexFormDataStructureArray['el'];
        $options['renderType'] = 'flexFormElementContainer';
        $containerContentResult = $this->nodeFactory->create($options)->render();

        $containerTitle = '';
        if (!empty(trim($flexFormDataStructureArray['title']))) {
            $containerTitle = $languageService->sL(trim($flexFormDataStructureArray['title']));
        }

        $resultArray = $this->initializeResultArray();

        $flexFormDomContainerId = sprintf('flexform-container-%s', $flexFormContainerIdentifier);
        $parentSectionContainer = sprintf('flexform-section-container-%s-%s', $this->data['fieldName'], $this->data['flexFormFieldName']);
        $containerAttributes = [
            'class' => 'form-irre-object panel panel-default panel-condensed t3js-flex-section',
            'data-parent' => $parentSectionContainer,
            'data-flexform-container-id' => $flexFormContainerIdentifier,
        ];

        $panelHeaderAttributes = [
            'class' => 'panel-heading',
            'data-bs-toggle' => 'flexform-inline',
            'data-bs-target' => '#' . $flexFormDomContainerId,
        ];

        $toggleAttributes = [
            'class' => 'form-irre-header-cell form-irre-header-button',
            'type' => 'button',
            'aria-controls' => $flexFormDomContainerId,
            'aria-expanded' => $flexFormContainerElementCollapsed ? 'false' : 'true',
        ];

        $html = [];
        $html[] = '<div ' . GeneralUtility::implodeAttributes($containerAttributes, true) . '>';
        $html[] =    '<input class="t3js-flex-control-action" type="hidden" name="' . htmlspecialchars($actionFieldName) . '" value="" />';
        $html[] =    '<div ' . GeneralUtility::implodeAttributes($panelHeaderAttributes, true) . '>';
        $html[] =        '<div class="form-irre-header ' . ($flexFormContainerElementCollapsed ? ' collapsed' : '') . '">';
        $html[] =            '<div class="form-irre-header-cell form-irre-header-icon">';
        $html[] =                '<span class="caret"></span>';
        $html[] =            '</div>';
        $html[] =            '<button ' . GeneralUtility::implodeAttributes($toggleAttributes, true) . '>';
        $html[] =                '<div class="form-irre-header-cell form-irre-header-body">';
        $html[] =                    htmlspecialchars($containerTitle);
        $html[] =                    '<output class="content-preview"></output>';
        $html[] =                '</div>';
        $html[] =            '</button>';
        $html[] =            '<div class="form-irre-header-cell form-irre-header-control t3js-formengine-irre-control">';
        $html[] =                '<div class="btn-group btn-group-sm">';
        $html[] =                    implode(LF, $moveAndDeleteContent);
        $html[] =                '</div>';
        $html[] =            '</div>';
        $html[] =        '</div>';
        $html[] =    '</div>';
        $html[] =    '<div id="' . htmlspecialchars($flexFormDomContainerId) . '" class="collapse t3js-flex-section-content ' . ($flexFormContainerElementCollapsed ? '' : 'show') . '">';
        $html[] =        $containerContentResult['html'];
        $html[] =    '</div>';
        $html[] =    '<input';
        $html[] =        'class="t3js-flex-control-toggle"';
        $html[] =        'type="hidden"';
        $html[] =        'id="flexform-toggle-container-' . htmlspecialchars($flexFormContainerIdentifier) . '"';
        $html[] =        'name="' . htmlspecialchars($toggleFieldName) . '"';
        $html[] =        'value="' . ($flexFormContainerElementCollapsed ? '1' : '0') . '"';
        $html[] =    '/>';
        $html[] = '</div>';

        $resultArray['html'] = implode(LF, $html);
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $containerContentResult, false);

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
