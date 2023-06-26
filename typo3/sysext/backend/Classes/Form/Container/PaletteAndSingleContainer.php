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

namespace TYPO3\CMS\Backend\Form\Container;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handle palettes and single fields.
 *
 * This container is called by TabsContainer, NoTabsContainer and ListOfFieldsContainer.
 *
 * This container mostly operates on TCA showItem of a specific type - the value is
 * coming in from upper containers as "fieldArray". It handles palettes with all its
 * different options and prepares rendering of single fields for the SingleFieldContainer.
 */
class PaletteAndSingleContainer extends AbstractContainer
{
    /**
     * Final result array accumulating results from children and final HTML
     *
     * @var array
     */
    protected $resultArray = [];

    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $languageService = $this->getLanguageService();

        /*
         * The first code block creates a target structure array to later create the final
         * HTML string. The single fields and sub containers are rendered here already and
         * other parts of the return array from children except html are accumulated in
         * $this->resultArray
         *
        $targetStructure = [
            0 => [
                'type' => 'palette',
                'fieldName' => 'palette1',
                'paletteLegend' => 'palette1',
                'paletteDescription' => 'palette1Description',
                'elements' => [
                    0 => [
                        'type' => 'single',
                        'fieldName' => 'paletteName',
                        // @deprecated: fieldLabel can be removed in v13 when all elements take care of label/legend
                        'fieldLabel' => 'element1',
                        'fieldHtml' => 'element1',
                    ),
                    1 => [
                        'type' => 'linebreak',
                    ),
                    2 => [
                        'type' => 'single',
                        'fieldName' => 'paletteName',
                        // @deprecated: fieldLabel can be removed in v13 when all elements take care of label/legend
                        'fieldLabel' => 'element2',
                        'fieldHtml' => 'element2',
                    ],
                ],
            ],
            1 => [
                'type' => 'single',
                'fieldName' => 'element3',
                // @deprecated: fieldLabel can be removed in v13 when all elements take care of label/legend
                'fieldLabel' => 'element3',
                'fieldHtml' => 'element3',
            ],
            2 => [
                'type' => 'palette',
                'fieldName' => 'palette2',
                'paletteLegend' => '', // Palette label is optional
                'paletteDescription' => '', // Palette description is optional
                'elements' => [
                    0 => [
                        'type' => 'single',
                        'fieldName' => 'element4',
                        // @deprecated: fieldLabel can be removed in v13 when all elements take care of label/legend
                        'fieldLabel' => 'element4',
                        'fieldHtml' => 'element4',
                    ],
                    1 => [
                        'type' => 'linebreak',
                    ],
                    2 => [
                        'type' => 'single',
                        'fieldName' => 'element5',
                        // @deprecated: fieldLabel can be removed in v13 when all elements take care of label/legend
                        'fieldLabel' => 'element5',
                        'fieldHtml' => 'element5',
                    ],
                ],
            ],
        );
         */

        // Create an intermediate structure of rendered sub elements and elements nested in palettes
        $targetStructure = [];
        $mainStructureCounter = -1;
        $fieldsArray = $this->data['fieldsArray'];
        $this->resultArray = $this->initializeResultArray();
        foreach ($fieldsArray as $fieldString) {
            $fieldConfiguration = $this->explodeSingleFieldShowItemConfiguration($fieldString);
            $fieldName = $fieldConfiguration['fieldName'];
            if ($fieldName === '--palette--') {
                $paletteElementArray = $this->createPaletteContentArray($fieldConfiguration['paletteName'] ?? '');
                if (!empty($paletteElementArray)) {
                    $mainStructureCounter++;
                    // If there is no label in ['types']['aType']['showitem'] for this palette: "--palette--;;aPalette",
                    // then use ['palettes']['aPalette']['label'] if given.
                    $paletteLegend = $fieldConfiguration['fieldLabel'];
                    if ($paletteLegend === null && !empty($this->data['processedTca']['palettes'][$fieldConfiguration['paletteName']]['label'])) {
                        $paletteLegend = $this->data['processedTca']['palettes'][$fieldConfiguration['paletteName']]['label'];
                    }
                    // Get description of palette.
                    $paletteDescription = $this->data['processedTca']['palettes'][$fieldConfiguration['paletteName']]['description'] ?? '';
                    $targetStructure[$mainStructureCounter] = [
                        'type' => 'palette',
                        'fieldName' => $fieldConfiguration['paletteName'],
                        'paletteLegend' => $languageService->sL($paletteLegend),
                        'paletteDescription' => $languageService->sL($paletteDescription),
                        'elements' => $paletteElementArray,
                    ];
                }
            } else {
                if (!is_array($this->data['processedTca']['columns'][$fieldName] ?? null)) {
                    continue;
                }
                $options = $this->data;
                $options['fieldName'] = $fieldName;
                $options['renderType'] = 'singleFieldContainer';
                $childResultArray = $this->nodeFactory->create($options)->render();
                if (!empty($childResultArray['html'])) {
                    $mainStructureCounter++;
                    $fieldLabel = '';
                    if (!empty($this->data['processedTca']['columns'][$fieldName]['label'])) {
                        $fieldLabel = $this->data['processedTca']['columns'][$fieldName]['label'];
                    }
                    $targetStructure[$mainStructureCounter] = [
                        'type' => 'single',
                        'fieldName' => $fieldConfiguration['fieldName'],
                        // @deprecated: fieldLabel can be removed in v13 when all elements take care of label/legend
                        'fieldLabel' => $fieldLabel,
                        'fieldHtml' => $childResultArray['html'],
                        // @deprecated since v12, will be removed with v13 when all elements handle label/legend on their own
                        'labelHasBeenHandled' => $childResultArray['labelHasBeenHandled'] ?? false,
                    ];
                }
                $this->resultArray = $this->mergeChildReturnIntoExistingResult($this->resultArray, $childResultArray, false);
            }
        }

        // Compile final content
        $content = [];
        foreach ($targetStructure as $element) {
            if ($element['type'] === 'palette') {
                $paletteName = $element['fieldName'];
                $isHiddenPalette = !empty($this->data['processedTca']['palettes'][$paletteName]['isHiddenPalette']);
                $html = [];
                $html[] = '<fieldset class="form-section' . ($isHiddenPalette ? ' hide' : '') . '">';
                if (!empty($element['paletteLegend'])) {
                    $html[] = '<h4 class="form-section-headline">' . htmlspecialchars($element['paletteLegend']) . '</h4>';
                }
                if (!empty($element['paletteDescription'])) {
                    $html[] = '<p class="form-section-description text-body-secondary">' . htmlspecialchars($element['paletteDescription']) . '</p>';
                }
                $html[] = '<div class="row">' . $this->renderInnerPaletteContent($element) . '</div>';
                $html[] = '</fieldset>';
                $content[] = implode(LF, $html);
            } else {
                $html = [];
                $html[] = '<fieldset class="form-section">';
                $html[] = $this->wrapSingleFieldContentWithLabelAndOuterDiv($element);
                $html[] = '</fieldset>';
                $content[] = implode(LF, $html);
            }
        }

        $finalResultArray = $this->resultArray;
        $finalResultArray['html'] = implode(LF, $content);
        return $finalResultArray;
    }

    /**
     * Render single fields of a given palette
     *
     * @param string $paletteName The palette to render
     */
    protected function createPaletteContentArray(string $paletteName): array
    {
        // palette needs a palette name reference, otherwise it does not make sense to try rendering of it
        if (empty($paletteName) || empty($this->data['processedTca']['palettes'][$paletteName]['showitem'])) {
            return [];
        }
        $resultStructure = [];
        $foundRealElement = false; // Set to true if not only line breaks were rendered
        $fieldsArray = GeneralUtility::trimExplode(',', $this->data['processedTca']['palettes'][$paletteName]['showitem'], true);
        foreach ($fieldsArray as $fieldString) {
            $fieldArray = $this->explodeSingleFieldShowItemConfiguration($fieldString);
            $fieldName = $fieldArray['fieldName'];
            if ($fieldName === '--linebreak--') {
                $resultStructure[] = [
                    'type' => 'linebreak',
                ];
            } else {
                if (!is_array($this->data['processedTca']['columns'][$fieldName] ?? null)) {
                    continue;
                }
                $options = $this->data;
                $options['fieldName'] = $fieldName;
                $options['renderType'] = 'singleFieldContainer';
                $singleFieldContentArray = $this->nodeFactory->create($options)->render();
                if (!empty($singleFieldContentArray['html'])) {
                    $foundRealElement = true;
                    $fieldLabel = '';
                    if (!empty($this->data['processedTca']['columns'][$fieldName]['label'])) {
                        $fieldLabel = $this->data['processedTca']['columns'][$fieldName]['label'];
                    }
                    $resultStructure[] = [
                        'type' => 'single',
                        'fieldName' => $fieldName,
                        'fieldLabel' => $fieldLabel,
                        'fieldHtml' => $singleFieldContentArray['html'],
                        // @deprecated since v12, will be removed with v13 when all elements handle label/legend on their own
                        'labelHasBeenHandled' => $singleFieldContentArray['labelHasBeenHandled'] ?? false,
                    ];
                }
                $this->resultArray = $this->mergeChildReturnIntoExistingResult($this->resultArray, $singleFieldContentArray, false);
            }
        }
        if ($foundRealElement) {
            return $resultStructure;
        }
        return [];
    }

    /**
     * Renders inner content of single elements of a palette and wrap it as needed
     *
     * @param array $elementArray Array of elements
     * @return string Wrapped content
     */
    protected function renderInnerPaletteContent(array $elementArray): string
    {
        // Group fields
        $groupedFields = [];
        $row = 0;
        $lastLineWasLinebreak = true;
        foreach ($elementArray['elements'] as $element) {
            if ($element['type'] === 'linebreak') {
                if (!$lastLineWasLinebreak) {
                    $row++;
                    $groupedFields[$row][] = $element;
                    $row++;
                    $lastLineWasLinebreak = true;
                }
            } else {
                $lastLineWasLinebreak = false;
                $groupedFields[$row][] = $element;
            }
        }

        $result = [];
        // Process fields
        foreach ($groupedFields as $fields) {
            $numberOfItems = count($fields);
            $colWidth = (int)floor(12 / $numberOfItems);
            // Column class calculation
            $colClass = 'col-md-12';
            $colClear = [];
            if ($colWidth == 6) {
                $colClass = 'col col-sm-6';
                $colClear = [
                    2 => 'd-sm-block d-md-none',
                ];
            } elseif ($colWidth === 4) {
                $colClass = 'col col-sm-4';
                $colClear = [
                    3 => 'd-sm-block d-md-none',
                ];
            } elseif ($colWidth === 3) {
                $colClass = 'col col-sm-6 col-md-3';
                $colClear = [
                    2 => 'd-sm-block d-md-none',
                    4 => 'd-sm-block d-md-block d-xl-none',
                ];
            } elseif ($colWidth <= 2) {
                $colClass = 'col col-sm-6 col-md-3 col-lg-2';
                $colClear = [
                    2 => 'd-sm-block',
                    4 => 'd-sm-block d-md-none',
                    6 => 'd-sm-block d-md-block d-lg-none',
                ];
            }

            // Render fields
            for ($counter = 0; $counter < $numberOfItems; $counter++) {
                $element = $fields[$counter];
                if ($element['type'] === 'linebreak') {
                    if ($counter !== $numberOfItems) {
                        $result[] = '<div class="clearfix"></div>';
                    }
                } else {
                    $result[] = $this->wrapSingleFieldContentWithLabelAndOuterDiv($element, [$colClass]);

                    // Breakpoints
                    if ($counter + 1 < $numberOfItems && !empty($colClear)) {
                        foreach ($colClear as $rowBreakAfter => $clearClass) {
                            if (($counter + 1) % $rowBreakAfter === 0) {
                                $result[] = '<div class="clearfix ' . $clearClass . '"></div>';
                            }
                        }
                    }
                }
            }
        }
        return implode(LF, $result);
    }

    /**
     * Wrap a single element
     *
     * @param array $element Given element as documented above
     * @param array $additionalPaletteClasses Additional classes to be added to HTML
     * @return string Wrapped element
     */
    protected function wrapSingleFieldContentWithLabelAndOuterDiv(array $element, array $additionalPaletteClasses = []): string
    {
        $paletteFieldClasses = array_merge(['form-group', 't3js-formengine-validation-marker', 't3js-formengine-palette-field'], $additionalPaletteClasses);
        $content = [];
        $content[] = '<div class="' . implode(' ', $paletteFieldClasses) . '">';
        if (!$element['labelHasBeenHandled']) {
            // @deprecated since v12, will be removed with v13 when all elements handle label/legend on their own
            $label = htmlspecialchars($element['fieldLabel']);
            if ($this->getBackendUser()->shallDisplayDebugInformation()) {
                $label .= ' <code>[' . htmlspecialchars($element['fieldName']) . ']</code>';
            }
            $content[] = '<label class="form-label t3js-formengine-label">';
            $content[] =     $label;
            $content[] = '</label>';
        }
        $content[] = $element['fieldHtml'];
        $content[] = '</div>';
        return implode(LF, $content);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
