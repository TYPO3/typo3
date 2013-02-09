<?php
namespace TYPO3\CMS\Fluid\Core\ViewHelper\Facets;

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
 * Post Parse Facet. Your view helper should implement this if you want a callback
 * to be called directly after the syntax tree node corresponding to this view
 * helper has been built.
 *
 * In the callback, it is possible to store some variables inside the
 * parseVariableContainer (which is different from the runtime variable container!).
 * This implicates that you usually have to adjust the \TYPO3\CMS\Fluid\View\TemplateView
 * in case you implement this facet.
 *
 * Normally, this facet is not needed, except in really really rare cases.
 */
interface PostParseInterface {

	/**
	 * Callback which is called directly after the corresponding syntax tree
	 * node to this view helper has been built.
	 * This is a parse-time callback, which does not change the rendering of a
	 * view helper.
	 *
	 * You can store some data inside the variableContainer given here, which
	 * can be used f.e. inside the TemplateView.
	 *
	 * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode $syntaxTreeNode The current node in the syntax tree corresponding to this view helper.
	 * @param array $viewHelperArguments View helper arguments as an array of SyntaxTrees. If you really need an argument, make sure to call $viewHelperArguments[$argName]->render(...)!
	 * @param \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer $variableContainer Variable container you can use to pass on some variables to the view.
	 * @return void
	 */
	static public function postParseEvent(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode $syntaxTreeNode, array $viewHelperArguments, \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer $variableContainer);

}

?>