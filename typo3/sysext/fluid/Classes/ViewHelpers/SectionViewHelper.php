<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * A Section view helper
 *
 * == Examples ==
 *
 * <code title="Rendering sections">
 * <f:section name="someSection">This is a section. {foo}</f:section>
 * <f:render section="someSection" arguments="{foo: someVariable}" />
 * </code>
 * <output>
 * the content of the section "someSection". The content of the variable {someVariable} will be available in the partial as {foo}
 * </output>
 *
 * <code title="Rendering recursive sections">
 * <f:section name="mySection">
 *  <ul>
 *    <f:for each="{myMenu}" as="menuItem">
 *      <li>
 *        {menuItem.text}
 *        <f:if condition="{menuItem.subItems}">
 *          <f:render section="mySection" arguments="{myMenu: menuItem.subItems}" />
 *        </f:if>
 *      </li>
 *    </f:for>
 *  </ul>
 * </f:section>
 * <f:render section="mySection" arguments="{myMenu: menu}" />
 * </code>
 * <output>
 * <ul>
 *   <li>menu1
 *     <ul>
 *       <li>menu1a</li>
 *       <li>menu1b</li>
 *     </ul>
 *   </li>
 * [...]
 * (depending on the value of {menu})
 * </output>
 *
 * @api
 */
class Tx_Fluid_ViewHelpers_SectionViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper implements Tx_Fluid_Core_ViewHelper_Facets_PostParseInterface, Tx_Fluid_Core_ViewHelper_Facets_CompilableInterface {

	/**
	 * Initialize the arguments.
	 *
	 * @return void
	 * @api
	 */
	public function initializeArguments() {
		$this->registerArgument('name', 'string', 'Name of the section', TRUE);
	}

	/**
	 * Save the associated view helper node in a static public class variable.
	 * called directly after the view helper was built.
	 *
	 * @param Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode $syntaxTreeNode
	 * @param array $viewHelperArguments
	 * @param Tx_Fluid_Core_ViewHelper_TemplateVariableContainer $variableContainer
	 * @return void
	 */
	static public function postParseEvent(Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode $syntaxTreeNode, array $viewHelperArguments, Tx_Fluid_Core_ViewHelper_TemplateVariableContainer $variableContainer) {
		$sectionName = $viewHelperArguments['name']->getText();
		if (!$variableContainer->exists('sections')) {
			$variableContainer->add('sections', array());
		}
		$sections = $variableContainer->get('sections');
		$sections[$sectionName] = $syntaxTreeNode;
		$variableContainer->remove('sections');
		$variableContainer->add('sections', $sections);
	}

	/**
	 * Rendering directly returns all child nodes.
	 *
	 * @return string HTML String of all child nodes.
	 * @api
	 */
	public function render() {
		if ($this->viewHelperVariableContainer->exists('Tx_Fluid_ViewHelpers_SectionViewHelper', 'isCurrentlyRenderingSection')) {
			$this->viewHelperVariableContainer->remove('Tx_Fluid_ViewHelpers_SectionViewHelper', 'isCurrentlyRenderingSection');
			return $this->renderChildren();
		}
		return '';
	}

	/**
	 * The inner contents of a section should not be rendered.
	 *
	 * @param string $argumentsVariableName
	 * @param string $renderChildrenClosureVariableName
	 * @param string $initializationPhpCode
	 * @param Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode $syntaxTreeNode
	 * @param Tx_Fluid_Core_Compiler_TemplateCompiler $templateCompiler
	 * @return string
	 */
	public function compile($argumentsVariableName, $renderChildrenClosureVariableName, &$initializationPhpCode, Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode $syntaxTreeNode, Tx_Fluid_Core_Compiler_TemplateCompiler $templateCompiler) {
		return '\'\'';
	}
}

?>
