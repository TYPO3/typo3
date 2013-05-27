<?php
namespace TYPO3\CMS\Fluid\Core\Widget;

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
 * This object stores the WidgetContext for the currently active widgets
 * of the current user, to make sure the WidgetContext is available in
 * Widget AJAX requests.
 *
 * This class is only used internally by the widget framework.
 */
class AjaxWidgetContextHolder implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * An array $ajaxWidgetIdentifier => $widgetContext
	 * which stores the widget context.
	 *
	 * @var array
	 */
	protected $widgetContexts = array();

	/**
	 * @var string
	 */
	protected $widgetContextsStorageKey = 'TYPO3\\CMS\\Fluid\\Core\\Widget\\AjaxWidgetContextHolder_widgetContexts';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->loadWidgetContexts();
	}

	/**
	 * Loads the windget contexts from the TYPO3 user session
	 *
	 * @return void
	 */
	protected function loadWidgetContexts() {
		if (TYPO3_MODE === 'FE') {
			$this->widgetContexts = unserialize($GLOBALS['TSFE']->fe_user->getKey('ses', $this->widgetContextsStorageKey));
		} else {
			$this->widgetContexts = unserialize($GLOBALS['BE_USER']->uc[$this->widgetContextsStorageKey]);
			$GLOBALS['BE_USER']->writeUC();
		}
	}

	/**
	 * Get the widget context for the given $ajaxWidgetId.
	 *
	 * @param string $ajaxWidgetId
	 * @return \TYPO3\CMS\Fluid\Core\Widget\WidgetContext
	 */
	public function get($ajaxWidgetId) {
		if (!isset($this->widgetContexts[$ajaxWidgetId])) {
			throw new \TYPO3\CMS\Fluid\Core\Widget\Exception\WidgetContextNotFoundException('No widget context was found for the Ajax Widget Identifier "' . $ajaxWidgetId . '". This only happens if AJAX URIs are called without including the widget on a page.', 1284793775);
		}
		return $this->widgetContexts[$ajaxWidgetId];
	}

	/**
	 * Stores the WidgetContext inside the Context, and sets the
	 * AjaxWidgetIdentifier inside the Widget Context correctly.
	 *
	 * @param \TYPO3\CMS\Fluid\Core\Widget\WidgetContext $widgetContext
	 * @return void
	 */
	public function store(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext $widgetContext) {
		$ajaxWidgetId = md5(uniqid(mt_rand(), TRUE));
		$widgetContext->setAjaxWidgetIdentifier($ajaxWidgetId);
		$this->widgetContexts[$ajaxWidgetId] = $widgetContext;
		$this->storeWidgetContexts();
	}

	/**
	 * Persists the widget contexts in the TYPO3 user session
	 *
	 * @return void
	 */
	protected function storeWidgetContexts() {
		if (TYPO3_MODE === 'FE') {
			$GLOBALS['TSFE']->fe_user->setKey('ses', $this->widgetContextsStorageKey, serialize($this->widgetContexts));
			$GLOBALS['TSFE']->fe_user->storeSessionData();
		} else {
			$GLOBALS['BE_USER']->uc[$this->widgetContextsStorageKey] = serialize($this->widgetContexts);
			$GLOBALS['BE_USER']->writeUc();
		}
	}
}

?>