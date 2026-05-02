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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Flex form container implementation
 * This one is called by FlexFormSectionContainer and renders HTML for a single container.
 * For processing of single elements FlexFormElementContainer is called
 */
class FlexFormContainerContainer extends AbstractContainer
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

        $table = $this->data['tableName'];
        $row = $this->data['databaseRow'];
        $fieldName = $this->data['fieldName'];
        $flexFormFormPrefix = $this->data['flexFormFormPrefix'];
        $flexFormDataStructureArray = $this->data['flexFormDataStructureArray'];

        $flexFormContainerIdentifier = $this->data['flexFormContainerIdentifier'];
        $actionFieldName = 'data[' . $table . '][' . $row['uid'] . '][' . $fieldName . ']'
            . $flexFormFormPrefix
            . '[' . $flexFormContainerIdentifier . ']'
            . '[_ACTION]';

        $moveAndDeleteContent = [];
        $userHasAccessToDefaultLanguage = $this->getBackendUserAuthentication()->checkLanguageAccess(0);
        if ($userHasAccessToDefaultLanguage) {
            $moveAndDeleteContent[] = ''
                . '<button type="button" class="btn btn-default t3js-delete">'
                . $this->iconFactory->getIcon('actions-edit-delete', IconSize::SMALL)->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:delete'))->render()
                . '</button>';
            $moveAndDeleteContent[] = ''
                . '<button type="button" class="btn btn-default t3js-sortable-handle sortableHandle">'
                . $this->iconFactory->getIcon('actions-move-move', IconSize::SMALL)->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:sortable.dragmove'))->render()
                . '</button>';
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

        $parentSectionContainer = sprintf('flexform-section-container-%s-%s-%s-%s', $this->data['flexFormSheetName'], $this->data['fieldName'], md5($this->data['flexFormFieldName']), md5($this->data['elementBaseName']));
        $flexFormDomContainerId = sprintf('%s-%s', $parentSectionContainer, $flexFormContainerIdentifier);
        $containerAttributes = [
            'class' => 'panel panel-default t3js-flex-section',
            'data-parent' => $parentSectionContainer,
            'data-flexform-container-id' => $flexFormContainerIdentifier,
        ];

        $panelHeaderAttributes = [
            'class' => 'panel-heading',
        ];

        $toggleAttributes = [
            'class' => 'panel-button collapsed',
            'type' => 'button',
            'data-bs-toggle' => 'collapse',
            'data-bs-target' => '#' . $flexFormDomContainerId,
            'aria-controls' => $flexFormDomContainerId,
            'aria-expanded' => 'false',
        ];

        $html = [];
        $html[] = '<div ' . GeneralUtility::implodeAttributes($containerAttributes, true) . '>';
        $html[] =    '<div ' . GeneralUtility::implodeAttributes($panelHeaderAttributes, true) . '>';
        $html[] =        '<div class="panel-heading-row">';
        $html[] =            '<button ' . GeneralUtility::implodeAttributes($toggleAttributes, true) . '>';
        $html[] =                '<span class="caret"></span>';
        $html[] =                '<div class="panel-title">';
        $html[] =                    '<output class="content-preview"></output>';
        $html[] =                    '<span class="panel-meta">' . htmlspecialchars($containerTitle) . '</span>';
        $html[] =                '</div>';
        $html[] =            '</button>';
        $html[] =            '<div class="panel-actions t3js-formengine-irre-control">';
        $html[] =                '<div class="btn-group btn-group-sm">';
        $html[] =                    implode(LF, $moveAndDeleteContent);
        $html[] =                '</div>';
        $html[] =            '</div>';
        $html[] =        '</div>';
        $html[] =    '</div>';
        $html[] =    '<div id="' . htmlspecialchars($flexFormDomContainerId) . '" class="panel-collapse collapse t3js-flex-section-content">';
        $html[] =       $containerContentResult['html'];
        $html[] =    '</div>';
        $html[] =    '<input class="t3js-flex-control-action" type="hidden" name="' . htmlspecialchars($actionFieldName) . '" value="" />';
        $html[] = '</div>';

        $resultArray['html'] = implode(LF, $html);
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $containerContentResult, false);

        return $resultArray;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
