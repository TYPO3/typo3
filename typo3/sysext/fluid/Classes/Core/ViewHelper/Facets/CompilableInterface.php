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
 * If a ViewHelper implements CompilableInterface, it can directly influence the way
 * the syntax tree is compiled to a static PHP file.
 *
 * For now, this class is NO API.
 *
 * There a two ways of using the Compilable Interface.
 *
 * Implementing renderStatic()
 * ===========================
 * A ViewHelper which implements CompilableInterface and the renderStatic method
 * is called *statically* through the renderStatic method; and no instance of the
 * ViewHelper is created.
 *
 * This is a case which you can implement if you do not access any *internal state*
 * in the ViewHelper. This means the following should be true:
 *
 * - you do not access $this->arguments, but only the function arguments of render()
 * - you do not call $this->renderChildren()
 *
 * If you have performance problems because the calling overhead of a ViewHelper
 * is too big, you should implement renderStatic().
 *
 * Implementing compile()
 * ======================
 *
 * Some ViewHelpers want to directly manipulate the PHP code which is created in
 * the compilation run. This is, however, only necessary in very special cases,
 * like in the AbstractConditionViewHelper or when a ViewHelper is potentially
 * called thousands of times.
 *
 * A ViewHelper which wants to directly influence the resulting PHP code must implement
 * the CompilableInterface, and only implement the compile() method.
 */
interface CompilableInterface {

	/**
	 * Here follows a more detailed description of the arguments of this function:
	 *
	 * $arguments contains a plain array of all arguments this ViewHelper has received,
	 * including the default argument values if an argument has not been specified
	 * in the ViewHelper invocation.
	 *
	 * $renderChildrenClosure is a closure you can execute instead of $this->renderChildren().
	 * It returns the rendered child nodes, so you can simply do $renderChildrenClosure() to execute
	 * it. It does not take any parameters.
	 *
	 * $renderingContext contains references to the TemplateVariableContainer, the
	 * ViewHelperVariableContainer and the ControllerContext.
	 *
	 * @param array $arguments
	 * @param \Closure $renderChildrenClosure
	 * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
	 * @return string the resulting string which is directly shown
	 */
	static public function renderStatic(array $arguments, \Closure $renderChildrenClosure, \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext);

	/**
	 * This method is called on compilation time.
	 *
	 * It has to return a *single* PHP statement without semi-colon or newline
	 * at the end, which will be embedded at various places.
	 *
	 * Furthermore, it can append PHP code to the variable $initializationPhpCode.
	 * In this case, all statements have to end with semi-colon and newline.
	 *
	 * Outputting new variables
	 * ========================
	 * If you want create a new PHP variable, you need to use
	 * $templateCompiler->variableName('nameOfVariable') for this, as all variables
	 * need to be globally unique.
	 *
	 * Return Value
	 * ============
	 * Besides returning a single string, it can also return the constant
	 * \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler::SHOULD_GENERATE_VIEWHELPER_INVOCATION
	 * which means that after the $initializationPhpCode, the ViewHelper invocation
	 * is built as normal. This is especially needed if you want to build new arguments
	 * at run-time, as it is done for the AbstractConditionViewHelper.
	 *
	 * @param string $argumentsVariableName Name of the variable in which the ViewHelper arguments are stored
	 * @param string $renderChildrenClosureVariableName Name of the closure which can be executed to render the child nodes
	 * @param string $initializationPhpCode
	 * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $syntaxTreeNode
	 * @param \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler $templateCompiler
	 * @return string
	 */
	public function compile($argumentsVariableName, $renderChildrenClosureVariableName, &$initializationPhpCode, \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $syntaxTreeNode, \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler $templateCompiler);
}

?>