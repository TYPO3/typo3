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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Widget_LinkViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractTagBasedViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'a';

	/**
	 * Initialize arguments
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
		$this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
		$this->registerTagAttribute('rev', 'string', 'Specifies the relationship between the linked document and the current document');
		$this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
	}

	/**
	 * Render the link.
	 *
	 * @param string $action Target action
	 * @param array $arguments Arguments
	 * @param string $section The anchor to be added to the URI
	 * @param string $format The requested format, e.g. ".html"
	 * @param boolean $ajax TRUE if the URI should be to an AJAX widget, FALSE otherwise.
	 * @return string The rendered link
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function render($action = NULL, $arguments = array(), $section = '', $format = '', $ajax = FALSE) {
		if ($ajax === TRUE) {
			$uri = $this->getAjaxUri();
		} else {
			$uri = $this->getWidgetUri();
		}
		$this->tag->addAttribute('href', $uri);
		$this->tag->setContent($this->renderChildren());

		return $this->tag->render();
	}

	/**
	 * Get the URI for an AJAX Request.
	 *
	 * @return string the AJAX URI
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function getAjaxUri() {
		$action = $this->arguments['action'];
		$arguments = $this->arguments['arguments'];

		if ($action === NULL) {
			$action = $this->controllerContext->getRequest()->getControllerActionName();
		}
		$arguments['id'] = $GLOBALS['TSFE']->id;
		// TODO page type should be configurable
		$arguments['type'] = 7076;
		$arguments['fluid-widget-id'] = $this->controllerContext->getRequest()->getWidgetContext()->getAjaxWidgetIdentifier();
		$arguments['action'] = $action;

		return '?' . http_build_query($arguments, NULL, '&');
	}

	/**
	 * Get the URI for a non-AJAX Request.
	 *
	 * @return string the Widget URI
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function getWidgetUri() {
		$uriBuilder = $this->controllerContext->getUriBuilder();

		$argumentPrefix = $this->controllerContext->getRequest()->getArgumentPrefix();
		$arguments = $this->arguments->hasArgument('arguments') ? $this->arguments['arguments'] : array();
		if ($this->arguments->hasArgument('action')) {
			$arguments['action'] = $this->arguments['action'];
		}
		if ($this->arguments->hasArgument('format') && $this->arguments['format'] !== '') {
			$arguments['format'] = $this->arguments['format'];
		}
		return $uriBuilder
			->reset()
			->setArguments(array($argumentPrefix => $arguments))
			->setSection($this->arguments['section'])
			->setCreateAbsoluteUri(TRUE)
			->setAddQueryString(TRUE)
			->setArgumentsToBeExcludedFromQueryString(array($argumentPrefix, 'cHash'))
			->setFormat($this->arguments['format'])
			->build();
	}
}

?>