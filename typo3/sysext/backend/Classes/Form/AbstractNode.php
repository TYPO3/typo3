<?php
namespace TYPO3\CMS\Backend\Form;

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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base class for container and single elements - their abstracts extend from here.
 */
abstract class AbstractNode implements NodeInterface {

	/**
	 * A list of global options given from parent to child elements
	 *
	 * @var array
	 */
	protected $globalOptions = array();

	/**
	 * Handler for single nodes
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	abstract public function render();

	/**
	 * Set global options from parent instance
	 *
	 * @param array $globalOptions Global options like 'readonly' for all elements
	 * @return $this
	 */
	public function setGlobalOptions(array $globalOptions) {
		$this->globalOptions = $globalOptions;
		return $this;
	}

	/**
	 * Initialize the array that is returned to parent after calling. This structure
	 * is identical for *all* nodes. Parent will merge the return of a child with its
	 * own stuff and in itself return an array of the same structure.
	 *
	 * @return array
	 */
	protected function initializeResultArray() {
		return array(
			'requiredElements' => array(), // name => value
			'requiredFields' => array(), // value => name
			'requiredAdditional' => array(), // name => array
			'requiredNested' => array(),
			'additionalJavaScriptPost' => array(),
			'additionalJavaScriptSubmit' => array(),
			'additionalHiddenFields' => array(),
			'additionalHeadTags' => array(),
			'extJSCODE' => '',
			'inlineData' => array(),
			'html' => '',
		);
	}

	/**
	 * Merge existing data with a child return array
	 *
	 * @param array $existing Currently merged array
	 * @param array $childReturn Array returned by child
	 * @return array Result array
	 */
	protected function mergeChildReturnIntoExistingResult(array $existing, array $childReturn) {
		if (!empty($childReturn['html'])) {
			$existing['html'] .= LF . $childReturn['html'];
		}
		if (!empty($childReturn['extJSCODE'])) {
			$existing['extJSCODE'] .= LF . $childReturn['extJSCODE'];
		}
		foreach ($childReturn['requiredElements'] as $name => $value) {
			$existing['requiredElements'][$name] = $value;
		}
		foreach ($childReturn['requiredFields'] as $value => $name) { // Params swapped ?!
			$existing['requiredFields'][$value] = $name;
		}
		foreach ($childReturn['requiredAdditional'] as $name => $subArray) {
			$existing['requiredAdditional'][$name] = $subArray;
		}
		foreach ($childReturn['requiredNested'] as $value => $name) {
			$existing['requiredNested'][$value] = $name;
		}
		foreach ($childReturn['additionalJavaScriptPost'] as $value) {
			$existing['additionalJavaScriptPost'][] = $value;
		}
		foreach ($childReturn['additionalJavaScriptSubmit'] as $value) {
			$existing['additionalJavaScriptSubmit'][] = $value;
		}
		foreach ($childReturn['additionalHiddenFields'] as $value) {
			$existing['additionalHiddenFields'][] = $value;
		}
		foreach ($childReturn['additionalHeadTags'] as $value) {
			$existing['additionalHeadTags'][] = $value;
		}
		if (!empty($childReturn['inlineData'])) {
			$existingInlineData = $existing['inlineData'];
			$childInlineData = $childReturn['inlineData'];
			ArrayUtility::mergeRecursiveWithOverrule($existingInlineData, $childInlineData);
			$existing['inlineData'] = $existingInlineData;
		}
		return $existing;
	}

	/**
	 * Determine and get the value for the placeholder for an input field.
	 * Typically used in an inline relation where values from fields down the record chain
	 * are used as "default" values for fields.
	 *
	 * @param string $table
	 * @param array $config
	 * @param array $row
	 * @return mixed
	 */
	protected function getPlaceholderValue($table, array $config, array $row) {
		$value = trim($config['placeholder']);
		if (!$value) {
			return '';
		}
		// Check if we have a reference to another field value from the current record
		if (substr($value, 0, 6) === '__row|') {
			/** @var FormDataTraverser $traverser */
			$traverseFields = GeneralUtility::trimExplode('|', substr($value, 6));
			$traverser = GeneralUtility::makeInstance(FormDataTraverser::class);
			$value = $traverser->getTraversedFieldValue($traverseFields, $table, $row, $this->globalOptions['inlineFirstPid']);
		}

		return $value;
	}

}
