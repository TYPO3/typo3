<?php
namespace TYPO3\CMS\Fluid\Core\Widget;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is backported from the TYPO3 Flow package "TYPO3.Fluid".
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Builds the WidgetRequest if an AJAX widget is called.
 */
class WidgetRequestBuilder extends \TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder {

	/**
	 * @var \TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder
	 */
	private $ajaxWidgetContextHolder;

	/**
	 * @param \TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder $ajaxWidgetContextHolder
	 * @return void
	 */
	public function injectAjaxWidgetContextHolder(\TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder $ajaxWidgetContextHolder) {
		$this->ajaxWidgetContextHolder = $ajaxWidgetContextHolder;
	}

	/**
	 * Builds a widget request object from the raw HTTP information
	 *
	 * @return \TYPO3\CMS\Fluid\Core\Widget\WidgetRequest The widget request as an object
	 */
	public function build() {
		$request = $this->objectManager->get('TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetRequest');
		$request->setRequestURI(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
		$request->setBaseURI(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
		$request->setMethod(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : NULL);
		if (strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
			$request->setArguments(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST());
		} else {
			$request->setArguments(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET());
		}
		$rawGetArguments = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();
		// TODO: rename to @action, to be consistent with normal naming?
		if (isset($rawGetArguments['action'])) {
			$request->setControllerActionName($rawGetArguments['action']);
		}
		$widgetContext = $this->ajaxWidgetContextHolder->get($rawGetArguments['fluid-widget-id']);
		$request->setWidgetContext($widgetContext);
		return $request;
	}
}
