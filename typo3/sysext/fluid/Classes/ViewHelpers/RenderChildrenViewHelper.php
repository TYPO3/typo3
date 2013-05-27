<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

/*
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
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
class RenderChildrenViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

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
	 * @throws \TYPO3\CMS\Fluid\Core\Widget\Exception\RenderingContextNotFoundException
	 * @return \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface
	 */
	protected function getWidgetRenderingContext() {
		$renderingContext = $this->getWidgetContext()->getViewHelperChildNodeRenderingContext();
		if (!$renderingContext instanceof \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface) {
			throw new \TYPO3\CMS\Fluid\Core\Widget\Exception\RenderingContextNotFoundException('Rendering Context not found inside Widget. <f:renderChildren> has been used in an AJAX Request, but is only usable in non-ajax mode.', 1284986604);
		}
		return $renderingContext;
	}

	/**
	 * @return \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode
	 */
	protected function getWidgetChildNodes() {
		return $this->getWidgetContext()->getViewHelperChildNodes();
	}

	/**
	 * @throws \TYPO3\CMS\Fluid\Core\Widget\Exception\WidgetRequestNotFoundException
	 * @return \TYPO3\CMS\Fluid\Core\Widget\WidgetContext
	 */
	protected function getWidgetContext() {
		$request = $this->controllerContext->getRequest();
		if (!$request instanceof \TYPO3\CMS\Fluid\Core\Widget\WidgetRequest) {
			throw new \TYPO3\CMS\Fluid\Core\Widget\Exception\WidgetRequestNotFoundException('The Request is not a WidgetRequest! <f:renderChildren> must be called inside a Widget Template.', 1284986120);
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