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
        $toggleIcons = '<span class="t3js-flex-control-toggle-icon-open" style="' . ($flexFormContainerElementCollapsed ? 'display: none;' : '') . '">'
            . $iconFactory->getIcon('actions-view-list-collapse', Icon::SIZE_SMALL)->render()
            . '</span>';
        $toggleIcons .= '<span class="t3js-flex-control-toggle-icon-close" style="' . ($flexFormContainerElementCollapsed ? '' : 'display: none;') . '">'
            . $iconFactory->getIcon('actions-view-list-expand', Icon::SIZE_SMALL)->render()
            . '</span>';

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
            $moveAndDeleteContent[] = '<span class="btn btn-default t3js-sortable-handle"><span title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:sortable.dragmove')) . '">' . $iconFactory->getIcon('actions-move-move', Icon::SIZE_SMALL)->render() . '</span></span>';
            $moveAndDeleteContent[] = '<span class="btn btn-default t3js-delete"><span title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:delete')) . '">' . $iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render() . '</span></span>';
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

        $html = [];
        $html[] = '<div class="t3-form-field-container-flexsections t3-flex-section t3js-flex-section">';
        $html[] =    '<input class="t3-flex-control t3js-flex-control-action" type="hidden" name="' . htmlspecialchars($actionFieldName) . '" value="" />';
        $html[] =    '<div class="panel panel-default panel-condensed">';
        $html[] =        '<div class="panel-heading t3js-flex-section-header" data-toggle="formengine-flex">';
        $html[] =            '<div class="form-irre-header">';
        $html[] =                '<div class="form-irre-header-cell form-irre-header-icon">';
        $html[] =                    $toggleIcons;
        $html[] =                '</div>';
        $html[] =                '<div class="form-irre-header-cell form-irre-header-body">';
        $html[] =                    '<span class="t3js-record-title">' . htmlspecialchars($containerTitle) . '</span>';
        $html[] =                '</div>';
        $html[] =                '<div class="form-irre-header-cell form-irre-header-control">';
        $html[] =                    '<div class="btn-group btn-group-sm">';
        $html[] =                        implode(LF, $moveAndDeleteContent);
        $html[] =                    '</div>';
        $html[] =                '</div>';
        $html[] =            '</div>';
        $html[] =        '</div>';
        $html[] =        '<div class="panel-collapse t3js-flex-section-content"' . ($flexFormContainerElementCollapsed ? ' style="display:none;"' : '') . '>';
        $html[] =            $containerContentResult['html'];
        $html[] =        '</div>';
        $html[] =        '<input';
        $html[] =            'class="t3-flex-control t3js-flex-control-toggle"';
        $html[] =            'type="hidden"';
        $html[] =            'name="' . htmlspecialchars($toggleFieldName) . '"';
        $html[] =            'value="' . ($flexFormContainerElementCollapsed ? '1' : '0') . '"';
        $html[] =        '/>';
        $html[] =    '</div>';
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
