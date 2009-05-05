<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id$
 */

/**
 * This view helper implements an if/else condition.
 * @see Tx_Fluid_Core_SyntaxTree_convertArgumentValue() to find see how boolean arguments are evaluated
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <f:if condition="somecondition">
 *   This is being shown in case the condition matches
 * </f:if>
 * </code>
 * 
 * Everything inside the <f3:if> tag is being displayed if the condition evaluates to TRUE.
 *
 * <code title="If / then / else">
 * <f3:if condition="somecondition">
 *   <f3:then>
 *     This is being shown in case the condition matches.
 *   </f3:then>
 *   <f3:else>
 *     This is being displayed in case the condition evaluates to FALSE.
 *   </f3:else>
 * </f3:if>
 * </code>
 * 
 * Everything inside the "then" tag is displayed if the condition evaluates to TRUE.
 * Otherwise, everything inside the "else"-tag is displayed.
 * 
 *
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_IfViewHelper extends Tx_Fluid_Core_AbstractViewHelper implements Tx_Fluid_Core_Facets_ChildNodeAccessInterface {

	/**
	 * An array of Tx_Fluid_Core_SyntaxTree_AbstractNode
	 * @var array
	 */
	protected $childNodes;

	/**
	 * Setter for ChildNodes - as defined in ChildNodeAccessInterface
	 *
	 * @param array $childNodes Child nodes of this syntax tree node
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setChildNodes(array $childNodes) {
		$this->childNodes = $childNodes;
	}

	/**
	 * renders <f:then> child if $condition is true, otherwise renders <f:else> child.
	 *
	 * @param boolean $condition View helper condition
	 * @return string the rendered string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render($condition) {
		if ($condition) {
			return $this->renderThenChild();
		} else {
			return $this->renderElseChild();
		}
	}

	/**
	 * Iterates through child nodes and renders ThenViewHelper.
	 * If no ThenViewHelper is found, all child nodes are rendered
	 *
	 * @return string rendered ThenViewHelper or contents of <f:if> if no ThenViewHelper was found
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function renderThenChild() {
		foreach ($this->childNodes as $childNode) {
			if ($childNode instanceof Tx_Fluid_Core_SyntaxTree_ViewHelperNode
				&& $childNode->getViewHelperClassName() === 'Tx_Fluid_ViewHelpers_ThenViewHelper' ) {
				return $childNode->render($this->variableContainer);
			}
		}
		return $this->renderChildren();
	}

	/**
	 * Iterates through child nodes and renders ElseViewHelper.
	 *
	 * @return string rendered ElseViewHelper or an empty string if no ThenViewHelper was found
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function renderElseChild() {
		foreach ($this->childNodes as $childNode) {
			if ($childNode instanceof Tx_Fluid_Core_SyntaxTree_ViewHelperNode
				&& $childNode->getViewHelperClassName() === 'Tx_Fluid_ViewHelpers_ElseViewHelper' ) {
				return $childNode->render($this->variableContainer);
			}
		}
		return '';
	}
}

?>
