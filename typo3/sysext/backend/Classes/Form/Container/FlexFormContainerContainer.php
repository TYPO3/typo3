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

use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Form\NodeFactory;

/**
 * Flex form container implementation
 * This one is called by FlexFormSectionContainer and renders HTML for a single container.
 * For processing of single elements FlexFormElementContainer is called
 */
class FlexFormContainerContainer extends AbstractContainer {

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$table = $this->globalOptions['table'];
		$row = $this->globalOptions['databaseRow'];
		$fieldName = $this->globalOptions['fieldName'];
		$flexFormFormPrefix = $this->globalOptions['flexFormFormPrefix'];
		$flexFormContainerElementCollapsed = $this->globalOptions['flexFormContainerElementCollapsed'];
		$flexFormContainerTitle = $this->globalOptions['flexFormContainerTitle'];
		$flexFormFieldIdentifierPrefix = $this->globalOptions['flexFormFieldIdentifierPrefix'];
		$parameterArray = $this->globalOptions['parameterArray'];

		// Every container adds its own part to the id prefix
		$flexFormFieldIdentifierPrefix = $flexFormFieldIdentifierPrefix . '-' . GeneralUtility::shortMd5(uniqid('id', TRUE));

		$toggleIcons = IconUtility::getSpriteIcon(
			'actions-move-down',
			array(
				'class' => 't3-flex-control-toggle-icon-open',
				'style' => $flexFormContainerElementCollapsed ? 'display: none;' : '',
			)
		);
		$toggleIcons .= IconUtility::getSpriteIcon(
			'actions-move-right',
			array(
				'class' => 't3-flex-control-toggle-icon-close',
				'style' => $flexFormContainerElementCollapsed ? '' : 'display: none;',
			)
		);

		$flexFormContainerCounter = $this->globalOptions['flexFormContainerCounter'];
		$actionFieldName = '_ACTION_FLEX_FORM'
			. $parameterArray['itemFormElName']
			. $this->globalOptions['flexFormFormPrefix']
			. '[_ACTION]'
			. '[' . $flexFormContainerCounter . ']';
		$toggleFieldName = 'data[' . $table . '][' . $row['uid'] . '][' . $fieldName . ']'
			. $flexFormFormPrefix
			. '[' . $flexFormContainerCounter . ']'
			. '[_TOGGLE]';

		$moveAndDeleteContent = array();
		$userHasAccessToDefaultLanguage = $this->getBackendUserAuthentication()->checkLanguageAccess(0);
		if ($userHasAccessToDefaultLanguage) {
			$moveAndDeleteContent[] = '<div class="pull-right">';
			$moveAndDeleteContent[] = IconUtility::getSpriteIcon(
				'actions-move-move',
				array(
					'title' => 'Drag to Move', // @todo: hardcoded title ...
					'class' => 't3-js-sortable-handle'
				)
			);
			$moveAndDeleteContent[] = IconUtility::getSpriteIcon(
				'actions-edit-delete',
				array(
					'title' => 'Delete', // @todo: hardcoded title ...
					'class' => 't3-js-delete'
				)
			);
			$moveAndDeleteContent[] = '</div>';
		}

		$options = $this->globalOptions;
		$options['flexFormFieldIdentifierPrefix'] = $flexFormFieldIdentifierPrefix;
		// Append container specific stuff to field prefix
		$options['flexFormFormPrefix'] =  $flexFormFormPrefix . '[' . $flexFormContainerCounter . '][' .  $this->globalOptions['flexFormContainerName'] . '][el]';
		$options['renderType'] = 'flexFormElementContainer';
		/** @var NodeFactory $nodeFactory */
		$nodeFactory = $this->globalOptions['nodeFactory'];
		$containerContentResult = $nodeFactory->create($options)->render();

		$html = array();
		$html[] = '<div id="' . $flexFormFieldIdentifierPrefix . '" class="t3-form-field-container-flexsections t3-flex-section">';
		$html[] = 	'<input class="t3-flex-control t3-flex-control-action" type="hidden" name="' . htmlspecialchars($actionFieldName) . '" value="" />';
		$html[] = 	'<div class="t3-form-field-header-flexsection t3-flex-section-header">';
		$html[] = 		'<div class="pull-left">';
		$html[] = 			'<a href="#" class="t3-flex-control-toggle-button">' . $toggleIcons . '</a>';
		$html[] = 			'<span class="t3-record-title">' . $flexFormContainerTitle . '</span>';
		$html[] = 		'</div>';
		$html[] = 		implode(LF, $moveAndDeleteContent);
		$html[] = 	'</div>';
		$html[] = 	'<div class="t3-form-field-record-flexsection t3-flex-section-content"' . ($flexFormContainerElementCollapsed ? ' style="display:none;"' : '') . '>';
		$html[] = 		$containerContentResult['html'];
		$html[] = 	'</div>';
		$html[] = 	'<input';
		$html[] = 		'class="t3-flex-control t3-flex-control-toggle"';
		$html[] = 		'id="' . $flexFormFieldIdentifierPrefix . '-toggleClosed"';
		$html[] = 		'type="hidden"';
		$html[] = 		'name="' . htmlspecialchars($toggleFieldName) . '"';
		$html[] = 		'value="' . ($flexFormContainerElementCollapsed ? '1' : '0') . '"';
		$html[] = 	'/>';
		$html[] = '</div>';

		$containerContentResult['html'] = '';
		$resultArray = $this->initializeResultArray();
		$resultArray['html'] = implode(LF, $html);
		$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $containerContentResult);

		return $resultArray;
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

}
