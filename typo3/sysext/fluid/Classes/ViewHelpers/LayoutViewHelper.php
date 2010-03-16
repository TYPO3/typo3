<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * With this tag, you can select a layout to be used..
 * <code><f:layout name="main" /></code>
 * 
 *
 *
 * @version $Id: LayoutViewHelper.php 1734 2009-11-25 21:53:57Z stucki $
 * @package Fluid
 * @subpackage ViewHelpers
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 * @todo refine documentation
 */
class Tx_Fluid_ViewHelpers_LayoutViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper implements Tx_Fluid_Core_ViewHelper_Facets_PostParseInterface {

	/**
	 * Initialize arguments
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function initializeArguments() {
		$this->registerArgument('name', 'string', 'Name of layout to use. If none given, "default" is used.', TRUE);
	}

	/**
	 * On the post parse event, add the "layoutName" variable to the variable container so it can be used by the TemplateView.
	 *
	 * @param Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode $syntaxTreeNode
	 * @param array $viewHelperArguments
	 * @param Tx_Fluid_Core_ViewHelper_TemplateVariableContainer $variableContainer
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	static public function postParseEvent(Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode $syntaxTreeNode, array $viewHelperArguments, Tx_Fluid_Core_ViewHelper_TemplateVariableContainer $variableContainer) {
		if (isset($viewHelperArguments['name'])) {
			$viewHelperArguments['name']->setRenderingContext(new Tx_Fluid_Core_Rendering_RenderingContext());
			$layoutName = $viewHelperArguments['name']->evaluate();
		} else {
			$layoutName = 'default';
		}

		$variableContainer->add('layoutName', $layoutName);
	}

	/**
	 * This tag will not be rendered at all.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function render() {
	}
}


?>
