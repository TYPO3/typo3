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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Handle palettes and single fields.
 *
 * This container is called by TabsContainer, NoTabsContainer and ListOfFieldsContainer.
 *
 * This container mostly operates on TCA showItem of a specific type - the value is
 * coming in from upper containers as "fieldArray". It handles palettes with all its
 * different options and prepares rendering of single fields for the SingleFieldContainer.
 */
class PaletteAndSingleContainer extends AbstractContainer {

	/**
	 * Final result array accumulating results from children and final HTML
	 *
	 * @var array
	 */
	protected $resultArray = array();

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$languageService = $this->getLanguageService();
		$table = $this->globalOptions['table'];

		/**
		 * The first code block creates a target structure array to later create the final
		 * HTML string. The single fields and sub containers are rendered here already and
		 * other parts of the return array from children except html are accumulated in
		 * $this->resultArray
		 *
		$targetStructure = array(
			0 => array(
				'type' => 'palette',
				'fieldName' => 'palette1',
				'fieldLabel' => 'palette1',
				'elements' => array(
					0 => array(
						'type' => 'single',
						'fieldName' => 'palettenName',
						'fieldLabel' => 'element1',
						'fieldHtml' => 'element1',
					),
					1 => array(
						'type' => 'linebreak',
					),
					2 => array(
						'type' => 'single',
						'fieldName' => 'palettenName',
						'fieldLabel' => 'element2',
						'fieldHtml' => 'element2',
					),
				),
			),
			1 => array( // has 2 as "additional palette"
				'type' => 'single',
				'fieldName' => 'element3',
				'fieldLabel' => 'element3',
				'fieldHtml' => 'element3',
			),
			2 => array( // do only if 1 had result
				'type' => 'palette2',
				'fieldName' => 'palette2',
				'fieldLabel' => '', // label missing because label of 1 is displayed only
				'canNotCollapse' => TRUE, // An "additional palette" can not be collapsed
				'elements' => array(
					0 => array(
						'type' => 'single',
						'fieldName' => 'element4',
						'fieldLabel' => 'element4',
						'fieldHtml' => 'element4',
					),
					1 => array(
						'type' => 'linebreak',
					),
					2 => array(
						'type' => 'single',
						'fieldName' => 'element5',
						'fieldLabel' => 'element5',
						'fieldHtml' => 'element5',
					),
				),
			),
		);
		 */

		// Create an intermediate structure of rendered sub elements and elements nested in palettes
		$targetStructure = array();
		$mainStructureCounter = -1;
		$fieldsArray = $this->globalOptions['fieldsArray'];
		$this->resultArray = $this->initializeResultArray();
		foreach ($fieldsArray as $fieldString) {
			$fieldConfiguration = $this->explodeSingleFieldShowItemConfiguration($fieldString);
			$fieldName = $fieldConfiguration['fieldName'];
			if ($fieldName === '--palette--') {
				$paletteElementArray = $this->createPaletteContentArray($fieldConfiguration['paletteName']);
				if (!empty($paletteElementArray)) {
					$mainStructureCounter ++;
					$targetStructure[$mainStructureCounter] = array(
						'type' => 'palette',
						'fieldName' => $fieldConfiguration['paletteName'],
						'fieldLabel' => $languageService->sL($fieldConfiguration['fieldLabel']),
						'elements' => $paletteElementArray,
					);
				}
			} else {
				if (!is_array($GLOBALS['TCA'][$table]['columns'][$fieldName])) {
					continue;
				}

				$options = $this->globalOptions;
				$options['fieldName'] = $fieldName;
				$options['fieldExtra'] = $fieldConfiguration['fieldExtra'];

				/** @var SingleFieldContainer $singleFieldContainer */
				$singleFieldContainer = GeneralUtility::makeInstance(SingleFieldContainer::class);
				$singleFieldContainer->setGlobalOptions($options);
				$childResultArray = $singleFieldContainer->render();

				if (!empty($childResultArray['html'])) {
					$mainStructureCounter ++;

					$targetStructure[$mainStructureCounter] = array(
						'type' => 'single',
						'fieldName' => $fieldConfiguration['fieldName'],
						'fieldLabel' => $this->getSingleFieldLabel($fieldName, $fieldConfiguration['fieldLabel']),
						'fieldHtml' => $childResultArray['html'],
					);

					// If the third part of a show item field is given, this is a name of a palette that should be rendered
					// below the single field - without palette header and only if single field produced content
					if (!empty($childResultArray['html']) && !empty($fieldConfiguration['paletteName'])) {
						$paletteElementArray = $this->createPaletteContentArray($fieldConfiguration['paletteName']);
						if (!empty($paletteElementArray)) {
							$mainStructureCounter ++;
							$targetStructure[$mainStructureCounter] = array(
								'type' => 'palette',
								'fieldName' => $fieldConfiguration['paletteName'],
								'fieldLabel' => '', // An "additional palette" has no show label
								'canNotCollapse' => TRUE,
								'elements' => $paletteElementArray,
							);
						}
					}
				}

				$childResultArray['html'] = '';
				$this->resultArray = $this->mergeChildReturnIntoExistingResult($this->resultArray, $childResultArray);
			}
		}

		// Compile final content
		$content = array();
		foreach ($targetStructure as $element) {
			if ($element['type'] === 'palette') {
				$paletteName = $element['fieldName'];
				$paletteElementsHtml = $this->renderInnerPaletteContent($element);

				$isHiddenPalette = !empty($GLOBALS['TCA'][$table]['palettes'][$paletteName]['isHiddenPalette']);

				$renderUnCollapseButtonWrapper = TRUE;
				// No button if the palette is hidden
				if ($isHiddenPalette) {
					$renderUnCollapseButtonWrapper = FALSE;
				}
				// No button if palette can not collapse on ctrl level
				if (!empty($GLOBALS['TCA'][$table]['ctrl']['canNotCollapse'])) {
					$renderUnCollapseButtonWrapper = FALSE;
				}
				// No button if palette can not collapse on palette definition level
				if (!empty($GLOBALS['TCA'][$table]['palettes'][$paletteName]['canNotCollapse'])) {
					$renderUnCollapseButtonWrapper = FALSE;
				}
				// No button if palettes are not collapsed - this is the checkbox at the end of the form
				if (!$this->globalOptions['palettesCollapsed']) {
					$renderUnCollapseButtonWrapper = FALSE;
				}
				// No button if palette is set to no collapse on element level - this is the case if palette is an "additional palette" after a casual field
				if (!empty($element['canNotCollapse'])) {
					$renderUnCollapseButtonWrapper = FALSE;
				}

				if ($renderUnCollapseButtonWrapper) {
					$cssId = 'FORMENGINE_' . $this->globalOptions['table'] . '_' . $paletteName . '_' . $this->globalOptions['databaseRow']['uid'];
					$paletteElementsHtml = $this->wrapPaletteWithCollapseButton($paletteElementsHtml, $cssId);
				} else {
					$paletteElementsHtml = '<div class="row">' . $paletteElementsHtml . '</div>';
				}

				$content[] = $this->fieldSetWrap($paletteElementsHtml, $isHiddenPalette, $element['fieldLabel']);
			} else {
				// Return raw HTML only in case of user element with no wrapping requested
				if ($this->isUserNoTableWrappingField($element)) {
					$content[] = $element['fieldHtml'];
				} else {
					$content[] = $this->fieldSetWrap($this->wrapSingleFieldContent($element));
				}
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
	 * @return array
	 */
	protected function createPaletteContentArray($paletteName) {
		$table = $this->globalOptions['table'];
		$excludeElements = $this->globalOptions['excludeElements'];

		// palette needs a palette name reference, otherwise it does not make sense to try rendering of it
		if (empty($paletteName) || empty($GLOBALS['TCA'][$table]['palettes'][$paletteName]['showitem'])) {
			return array();
		}

		$resultStructure = array();
		$foundRealElement = FALSE; // Set to true if not only line breaks were rendered
		$fieldsArray = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['palettes'][$paletteName]['showitem'], TRUE);
		foreach ($fieldsArray as $fieldString) {
			$fieldArray = $this->explodeSingleFieldShowItemConfiguration($fieldString);
			$fieldName = $fieldArray['fieldName'];
			if ($fieldName === '--linebreak--') {
				$resultStructure[] = array(
					'type' => 'linebreak',
				);
			} else {
				if (in_array($fieldName, $excludeElements, TRUE) || !is_array($GLOBALS['TCA'][$table]['columns'][$fieldName])) {
					continue;
				}
				$options = $this->globalOptions;
				$options['fieldName'] = $fieldName;
				$options['fieldExtra'] = $fieldArray['fieldExtra'];

				/** @var SingleFieldContainer $singleFieldContainer */
				$singleFieldContainer = GeneralUtility::makeInstance(SingleFieldContainer::class);
				$singleFieldContainer->setGlobalOptions($options);
				$singleFieldContentArray = $singleFieldContainer->render();

				if (!empty($singleFieldContentArray['html'])) {
					$foundRealElement = TRUE;
					$resultStructure[] = array(
						'type' => 'single',
						'fieldName' => $fieldName,
						'fieldLabel' => $this->getSingleFieldLabel($fieldName, $fieldArray['fieldLabel']),
						'fieldHtml' => $singleFieldContentArray['html'],
					);
					$singleFieldContentArray['html'] = '';
				}
				$this->resultArray = $this->mergeChildReturnIntoExistingResult($this->resultArray, $singleFieldContentArray);
			}
		}

		if ($foundRealElement) {
			return $resultStructure;
		} else {
			return array();
		}
	}

	/**
	 * Renders inner content of single elements of a palette and wrap it as needed
	 *
	 * @param array $elementArray Array of elements
	 * @return string Wrapped content
	 */
	protected function renderInnerPaletteContent(array $elementArray) {
		// Group fields
		$groupedFields = array();
		$row = 0;
		$lastLineWasLinebreak = TRUE;
		foreach ($elementArray['elements'] as $element) {
			if ($element['type'] === 'linebreak') {
				if (!$lastLineWasLinebreak) {
					$row++;
					$groupedFields[$row][] = $element;
					$row++;
					$lastLineWasLinebreak = TRUE;
				}
			} else {
				$lastLineWasLinebreak = FALSE;
				$groupedFields[$row][] = $element;
			}
		}

		$result = array();
		// Process fields
		foreach ($groupedFields as $fields) {
			$numberOfItems = count($fields);
			$colWidth = (int)floor(12 / $numberOfItems);
			// Column class calculation
			$colClass = "col-md-12";
			$colClear = array();
			if ($colWidth == 6) {
				$colClass = "col-sm-6";
				$colClear = array(
					2 => 'visible-sm-block visible-md-block visible-lg-block',
				);
			} elseif ($colWidth === 4) {
				$colClass = "col-sm-4";
				$colClear = array(
					3 => 'visible-sm-block visible-md-block visible-lg-block',
				);
			} elseif ($colWidth === 3) {
				$colClass = "col-sm-6 col-md-3";
				$colClear = array(
					2 => 'visible-sm-block',
					4 => 'visible-md-block visible-lg-block',
				);
			} elseif ($colWidth <= 2) {
				$colClass = "checkbox-column col-sm-6 col-md-3 col-lg-2";
				$colClear = array(
					2 => 'visible-sm-block',
					4 => 'visible-md-block',
					6 => 'visible-lg-block'
				);
			}

			// Render fields
			for ($counter = 0; $counter < $numberOfItems; $counter++) {
				$element = $fields[$counter];
				if ($element['type'] === 'linebreak') {
					if ($counter !== $numberOfItems) {
						$result[] = '<div class="clearfix"></div>';
					}
				} else {
					$result[] = $this->wrapSingleFieldContent($element, array($colClass));

					// Breakpoints
					if ($counter + 1 < $numberOfItems && !empty($colClear)) {
						foreach ($colClear as $rowBreakAfter => $clearClass) {
							if (($counter + 1) % $rowBreakAfter === 0) {
								$result[] = '<div class="clearfix '. $clearClass . '"></div>';
							}
						}
					}
				}
			}
		}

		return implode(LF, $result);
	}

	/**
	 * Add a "collapsible" button around given content
	 *
	 * @param string $elementHtml HTML of handled palette content
	 * @param string $cssId A css id to be added
	 * @return string Wrapped content
	 */
	protected function wrapPaletteWithCollapseButton($elementHtml, $cssId) {
		$content = array();
		$content[] = '<p>';
		$content[] = 	'<button class="btn btn-default" type="button" data-toggle="collapse" data-target="#' . $cssId . '" aria-expanded="false" aria-controls="' . $cssId . '">';
		$content[] = 		IconUtility::getSpriteIcon('actions-system-options-view');
		$content[] = 		htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.moreOptions'));
		$content[] = 	'</button>';
		$content[] = '</p>';
		$content[] = '<div id="' . $cssId . '" class="form-section-collapse collapse">';
		$content[] = 	'<div class="row">' . $elementHtml . '</div>';
		$content[] = '</div>';
		return implode(LF, $content);
	}

	/**
	 * Wrap content in a field set
	 *
	 * @param string $content Incoming content
	 * @param bool $paletteHidden TRUE if the palette is hidden
	 * @param string $label Given label
	 * @return string Wrapped content
	 */
	protected function fieldSetWrap($content, $paletteHidden = FALSE, $label = '') {
		$fieldSetClass = 'form-section';
		if ($paletteHidden) {
			$fieldSetClass = 'hide';
		}

		$result = array();
		$result[] = '<fieldset class="' . $fieldSetClass . '">';

		if (!empty($label)) {
			$result[] = '<h4 class="form-section-headline">' . htmlspecialchars($label) . '</h4>';
		}

		$result[] = $content;
		$result[] = '</fieldset>';
		return implode(LF, $result);
	}

	/**
	 * Wrap a single element
	 *
	 * @param array $element Given element as documented above
	 * @param array $additionalPaletteClasses Additional classes to be added to HTML
	 * @return string Wrapped element
	 */
	protected function wrapSingleFieldContent(array $element, array $additionalPaletteClasses = array()) {
		$fieldName = $element['fieldName'];

		$paletteFieldClasses = array(
			'form-group',
			't3js-formengine-palette-field',
		);
		foreach ($additionalPaletteClasses as $class) {
			$paletteFieldClasses[] = $class;
		}

		$fieldItemClasses = array(
			't3js-formengine-field-item'
		);
		$isNullValueField = $this->isDisabledNullValueField($fieldName);
		if ($isNullValueField) {
			$fieldItemClasses[] = 'disabled';
		}

		$label = BackendUtility::wrapInHelp($this->globalOptions['table'], $fieldName, htmlspecialchars($element['fieldLabel']));

		$content = array();
		$content[] = '<div class="' . implode(' ', $paletteFieldClasses) . '">';
		$content[] = 	'<label class="t3js-formengine-label">';
		$content[] = 		$label;
		$content[] = 		'<img name="req_' . $this->globalOptions['table'] . '_' . $this->globalOptions['databaseRow']['uid'] . '_' . $fieldName . '" src="clear.gif" class="t3js-formengine-field-required" alt="" />';
		$content[] = 	'</label>';
		$content[] = 	'<div class="' . implode(' ', $fieldItemClasses) . '">';
		$content[] = 		'<div class="t3-form-field-disable"></div>';
		$content[] = 		$this->renderNullValueWidget($fieldName);
		$content[] = 		$element['fieldHtml'];
		$content[] = 	'</div>';
		$content[] = '</div>';

		return implode(LF, $content);
	}

	/**
	 * Determine label of a single field (not a palette label)
	 *
	 * @param string $fieldName The field name to calculate the label for
	 * @param string $labelFromShowItem Given label, typically from show item configuration
	 * @return string Field label
	 */
	protected function getSingleFieldLabel($fieldName, $labelFromShowItem) {
		$languageService = $this->getLanguageService();
		$table = $this->globalOptions['table'];
		$label = $labelFromShowItem;
		if (!empty($GLOBALS['TCA'][$table]['columns'][$fieldName]['label'])) {
			$label = $GLOBALS['TCA'][$table]['columns'][$fieldName]['label'];
		}
		if (!empty($labelFromShowItem)) {
			$label = $labelFromShowItem;
		}
		$fieldTSConfig = FormEngineUtility::getTSconfigForTableRow($table, $this->globalOptions['databaseRow'], $fieldName);
		if (!empty($fieldTSConfig['label'])) {
			$label = $fieldTSConfig['label'];
		}
		if (!empty($fieldTSConfig['label.'][$languageService->lang])) {
			$label = $fieldTSConfig['label.'][$languageService->lang];
		}
		return $languageService->sL($label);
	}

	/**
	 * TRUE if field is of type user and to wrapping is requested
	 *
	 * @param array $element Current element from "target structure" array
	 * @return boolean TRUE if user and noTableWrapping is set
	 */
	protected function isUserNoTableWrappingField($element) {
		$table = $this->globalOptions['table'];
		$fieldName = $element['fieldName'];
		if (
			$GLOBALS['TCA'][$table]['columns'][$fieldName]['config']['type'] === 'user'
			&& !empty($GLOBALS['TCA'][$table]['columns'][$fieldName]['config']['noTableWrapping'])
		) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Determines whether the current field value is considered as NULL value.
	 * Using NULL values is enabled by using 'null' in the 'eval' TCA definition.
	 * If NULL value is possible for a field and additional checkbox next to the element will be rendered.
	 *
	 * @param string $fieldName The field to handle
	 * @return bool
	 */
	protected function isDisabledNullValueField($fieldName) {
		$table = $this->globalOptions['table'];
		$row = $this->globalOptions['databaseRow'];
		$config = $GLOBALS['TCA'][$table]['columns'][$fieldName]['config'];
		$result = FALSE;
		$value = $row[$fieldName];
		if (
			$value === NULL
			&& !empty($config['eval']) && GeneralUtility::inList($config['eval'], 'null')
			&& (empty($config['mode']) || $config['mode'] !== 'useOrOverridePlaceholder')
		) {
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * Renders a view widget to handle and activate NULL values.
	 * The widget is enabled by using 'null' in the 'eval' TCA definition.
	 *
	 * @param string $fieldName The field to handle
	 * @return string
	 */
	protected function renderNullValueWidget($fieldName) {
		$table = $this->globalOptions['table'];
		$row = $this->globalOptions['databaseRow'];
		$value = $row[$fieldName];
		$config = $GLOBALS['TCA'][$table]['columns'][$fieldName]['config'];

		$widget = array();
		// Checkbox should be rendered if eval null set and no override stuff is done
		if (
			!empty($config['eval']) && GeneralUtility::inList($config['eval'], 'null')
			&& (empty($config['mode']) || $config['mode'] !== 'useOrOverridePlaceholder')
		) {
			$checked = $value === NULL ? '' : ' checked="checked"';
			$formElementName = $this->globalOptions['prependFormFieldNames'] . '[' . $table . '][' . $row['uid'] . '][' . $fieldName . ']';
			$formElementNameActive = $this->globalOptions['prependFormFieldNamesActive'] . '[' . $table . '][' . $row['uid'] . '][' . $fieldName . ']';
			$onChange = htmlspecialchars(
				'typo3form.fieldSetNull(\'' . $formElementName . '\', !this.checked)'
			);

			$widget = array();
			$widget[] = '<div class="checkbox">';
			$widget[] = 	'<label>';
			$widget[] = 		'<input type="hidden" name="' . $formElementNameActive . '" value="0" />';
			$widget[] = 		'<input type="checkbox" name="' . $formElementNameActive . '" value="1" onchange="' . $onChange . '"' . $checked . ' /> &nbsp;';
			$widget[] = 	'</label>';
			$widget[] = '</div>';
		}

		return implode(LF, $widget);
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}