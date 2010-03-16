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
 * Post Parse Facet. Your view helper should implement this if you want a callback
 * to be called directly after the syntax tree node corresponding to this view
 * helper has been built.
 *
 * In the callback, it is possible to store some variables inside the
 * parseVariableContainer (which is different from the runtime variable container!).
 * This implicates that you usually have to adjust the Tx_Fluid_View_TemplateView
 * in case you implement this facet.
 *
 * Normally, this facet is not needed, except in really really rare cases.
 *
 * @version $Id: PostParseInterface.php 1734 2009-11-25 21:53:57Z stucki $
 * @package Fluid
 * @subpackage Core\ViewHelper\Facets
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface Tx_Fluid_Core_ViewHelper_Facets_PostParseInterface {

	/**
	 * Callback which is called directly after the corresponding syntax tree
	 * node to this view helper has been built.
	 * This is a parse-time callback, which does not change the rendering of a
	 * view helper.
	 *
	 * You can store some data inside the variableContainer given here, which
	 * can be used f.e. inside the TemplateView.
	 *
	 * @param Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode $syntaxTreeNode The current node in the syntax tree corresponding to this view helper.
	 * @param array $viewHelperArguments View helper arguments as an array of SyntaxTrees. If you really need an argument, make sure to call $viewHelperArguments[$argName]->render(...)!
	 * @param Tx_Fluid_Core_ViewHelper_TemplateVariableContainer $variableContainer Variable container you can use to pass on some variables to the view.
	 * @return void
	 */
	static public function postParseEvent(Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode $syntaxTreeNode, array $viewHelperArguments, Tx_Fluid_Core_ViewHelper_TemplateVariableContainer $variableContainer);

}

?>