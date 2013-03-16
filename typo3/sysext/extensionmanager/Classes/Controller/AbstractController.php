<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog, <typo3@susannemoog.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Abstract action controller.
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	const TRIGGER_RefreshModuleMenu = 'refreshModuleMenu';

	/**
	 * @var array
	 */
	protected $triggerArguments = array(
		self::TRIGGER_RefreshModuleMenu,
	);

	/**
	 * Resolve view and initialize the general view-variables extensionName,
	 * controllerName and actionName based on the request object
	 *
	 * @return \TYPO3\CMS\Fluid\View\TemplateView
	 */
	protected function resolveView() {
		$view = parent::resolveView();
		$view->assignMultiple(array(
			'extensionName' => $this->request->getControllerExtensionName(),
			'controllerName' => $this->request->getControllerName(),
			'actionName' => $this->request->getControllerActionName()
		));
		return $view;
	}

	/**
	 * Translation shortcut
	 *
	 * @param $key
	 * @param NULL|array $arguments
	 * @return NULL|string
	 */
	protected function translate($key, $arguments = NULL) {
		return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'extensionmanager', $arguments);
	}

	/**
	 * Handles trigger arguments, e.g. refreshing the module menu
	 * widget if an extension with backend modules has been enabled
	 * or disabled.
	 *
	 * @return void
	 */
	protected function handleTriggerArguments() {
		$triggers = array();

		foreach ($this->triggerArguments as $triggerArgument) {
			if ($this->request->hasArgument($triggerArgument)) {
				$triggers[$triggerArgument] = $this->request->getArgument($triggerArgument);
			}
		}

		$this->view->assign('triggers', $triggers);
	}
}

?>