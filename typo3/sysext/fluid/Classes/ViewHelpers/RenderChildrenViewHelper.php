<?php

/*
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
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
 * Render the inner parts of a Widget.
 * This ViewHelper can only be used in a template which belongs to a Widget Controller.
 *
 * It renders everything inside the Widget ViewHelper, and you can pass additional
 * arguments.
 *
 * @api
 */
class Tx_Fluid_ViewHelpers_RenderChildrenViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * @param array $arguments
	 * @return string
	 */
	public function render(array $arguments = array()) {
		$renderingContext = $this->getWidgetRenderingContext();
		$widgetChildNodes = $this->getWidgetChildNodes();

		$this->addArgumentsToTemplateVariableContainer($arguments);
		$output = $widgetChildNodes->evaluate($renderingContext);
		$this->removeArgumentsFromTemplateVariableContainer($arguments);

		return $output;
	}

	/**
	 * Get the widget rendering context, or throw an exception if it cannot be found.
	 *
	 * @return Tx_Fluid_Core_Rendering_RenderingContextInterface
	 */
	protected function getWidgetRenderingContext() {
		$renderingContext = $this->getWidgetContext()->getViewHelperChildNodeRenderingContext();
		if (!($renderingContext instanceof Tx_Fluid_Core_Rendering_RenderingContextInterface)) {
			throw new Tx_Fluid_Core_Widget_Exception_RenderingContextNotFoundException('Rendering Context not found inside Widget. <f:renderChildren> has been used in an AJAX Request, but is only usable in non-ajax mode.', 1284986604);
		}
		return $renderingContext;
	}

	/**
	 * @return Tx_Fluid_Core_Parser_SyntaxTree_RootNode
	 */
	protected function getWidgetChildNodes() {
		return $this->getWidgetContext()->getViewHelperChildNodes();
	}

	/**
	 * @return Tx_Fluid_Core_Widget_WidgetContext
	 */
	protected function getWidgetContext() {
		$request = $this->controllerContext->getRequest();
		if (!($request instanceof Tx_Fluid_Core_Widget_WidgetRequest)) {
			throw new Tx_Fluid_Core_Widget_Exception_WidgetRequestNotFoundException('The Request is not a WidgetRequest! <f:renderChildren> must be called inside a Widget Template.', 1284986120);
		}

		return $request->getWidgetContext();
	}

	/**
	 * Add the given arguments to the TemplateVariableContainer of the widget.
	 *
	 * @param array $arguments
	 * @return void
	 */
	protected function addArgumentsToTemplateVariableContainer(array $arguments) {
		$templateVariableContainer = $this->getWidgetRenderingContext()->getTemplateVariableContainer();
		foreach ($arguments as $identifier => $value) {
			$templateVariableContainer->add($identifier, $value);
		}
	}

	/**
	 * Remove the given arguments from the TemplateVariableContainer of the widget.
	 *
	 * @param array $arguments
	 * @return void
	 */
	protected function removeArgumentsFromTemplateVariableContainer(array $arguments) {
		$templateVariableContainer = $this->getWidgetRenderingContext()->getTemplateVariableContainer();
		foreach ($arguments as $identifier => $value) {
			$templateVariableContainer->remove($identifier);
		}
	}
}
?>