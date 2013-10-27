<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Case view helper that is only usable within the SwitchViewHelper.
 * @see \TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper
 *
 * @api
 */
class CaseViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @param mixed $value The switch value. If it matches, the child will be rendered
	 * @param boolean $default If this is set, this child will be rendered, if none else matches
	 *
	 * @return string the contents of this view helper if $value equals the expression of the surrounding switch view helper, or $default is TRUE. otherwise an empty string
	 * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 *
	 * @api
	 */
	public function render($value = NULL, $default = FALSE) {
		$viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
		if (!$viewHelperVariableContainer->exists('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')) {
			throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('The case View helper can only be used within a switch View helper', 1368112037);
		}
		if (is_null($value) && $default === FALSE) {
			throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('The case View helper must have either value or default argument', 1382867521);
		}
		$switchExpression = $viewHelperVariableContainer->get('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression');

		// non-type-safe comparison by intention
		if ($default === TRUE || $switchExpression == $value) {
			$viewHelperVariableContainer->addOrUpdate('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'break', TRUE);
			return $this->renderChildren();
		}
		return '';
	}
}
