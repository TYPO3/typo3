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
 * Node which will call a ViewHelper associated with this node.
 *
 */
class Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode extends Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode {

	/**
	 * Class name of view helper
	 * @var string
	 */
	protected $viewHelperClassName;

	/**
	 * Arguments of view helper - References to RootNodes.
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * The ViewHelper associated with this node
	 * @var Tx_Fluid_Core_ViewHelper_AbstractViewHelper
	 */
	protected $uninitializedViewHelper = NULL;

	/**
	 * A mapping RenderingContext -> ViewHelper to only re-initialize ViewHelpers
	 * when a context change occurs.
	 * @var Tx_Extbase_Persistence_ObjectStorage
	 */
	protected $viewHelpersByContext = NULL;



	/**
	 * Constructor.
	 *
	 * @param Tx_Fluid_Core_ViewHelper_AbstractViewHelper $viewHelper The view helper
	 * @param array $arguments Arguments of view helper - each value is a RootNode.
	 */
	public function __construct(Tx_Fluid_Core_ViewHelper_AbstractViewHelper $viewHelper, array $arguments) {
		$this->uninitializedViewHelper = $viewHelper;
		$this->viewHelpersByContext = t3lib_div::makeInstance('Tx_Extbase_Persistence_ObjectStorage');
		$this->arguments = $arguments;
		$this->viewHelperClassName = get_class($this->uninitializedViewHelper);
	}

	/**
	 * Returns the attached (but still uninitialized) ViewHelper for this ViewHelperNode.
	 * We need this method because sometimes Interceptors need to ask some information from the ViewHelper.
	 *
	 * @return Tx_Fluid_Core_ViewHelper_AbstractViewHelper the attached ViewHelper, if it is initialized
	 */
	public function getUninitializedViewHelper() {
		return $this->uninitializedViewHelper;
	}

	/**
	 * Get class name of view helper
	 *
	 * @return string Class Name of associated view helper
	 */
	public function getViewHelperClassName() {
		return $this->viewHelperClassName;
	}

	/**
	 * INTERNAL - only needed for compiling templates
	 *
	 * @return array
	 * @internal
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Call the view helper associated with this object.
	 *
	 * First, it evaluates the arguments of the view helper.
	 *
	 * If the view helper implements Tx_Fluid_Core_ViewHelper_Facets_ChildNodeAccessInterface,
	 * it calls setChildNodes(array childNodes) on the view helper.
	 *
	 * Afterwards, checks that the view helper did not leave a variable lying around.
	 *
	 * @param Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext
	 * @return object evaluated node after the view helper has been called.
	 */
	public function evaluate(Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext) {
		$objectManager = $renderingContext->getObjectManager();
		$contextVariables = $renderingContext->getTemplateVariableContainer()->getAllIdentifiers();

		if ($this->viewHelpersByContext->contains($renderingContext)) {
			$viewHelper = $this->viewHelpersByContext[$renderingContext];
		} else {
			$viewHelper = clone $this->uninitializedViewHelper;
			$this->viewHelpersByContext->attach($renderingContext, $viewHelper);
		}

		$evaluatedArguments = array();
		if (count($viewHelper->prepareArguments())) {
 			foreach ($viewHelper->prepareArguments() as $argumentName => $argumentDefinition) {
				if (isset($this->arguments[$argumentName])) {
					$argumentValue = $this->arguments[$argumentName];
					$evaluatedArguments[$argumentName] = $argumentValue->evaluate($renderingContext);
				} else {
					$evaluatedArguments[$argumentName] = $argumentDefinition->getDefaultValue();
				}
			}
		}

		$viewHelper->setArguments($evaluatedArguments);
		$viewHelper->setViewHelperNode($this);
		$viewHelper->setRenderingContext($renderingContext);

		if ($viewHelper instanceof Tx_Fluid_Core_ViewHelper_Facets_ChildNodeAccessInterface) {
			$viewHelper->setChildNodes($this->childNodes);
		}

		$output = $viewHelper->initializeArgumentsAndRender();

		return $output;
	}

	/**
	 * Clean up for serializing.
	 *
	 * @return array
	 */
	public function __sleep() {
		return array('viewHelperClassName', 'arguments', 'childNodes');
	}
}

?>