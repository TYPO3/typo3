<?php
namespace TYPO3\CMS\Scheduler\ViewHelpers;

/*
 * This file is part of the TYPO3 CMS project.
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Create internal link tag within backend app
 * @internal
 */
class ModuleLinkViewHelper extends AbstractTagBasedViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'a';

	/**
	 * Arguments initialization
	 *
	 * @return void
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
		$this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
		$this->registerTagAttribute('rev', 'string', 'Specifies the relationship between the linked document and the current document');
		$this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
	}

	/**
	 * Render module link with command and arguments
	 *
	 * @param string $controller The "controller" of scheduler. Possible values are "scheduler", "check", "info"
	 * @param string $action The action to be called within each controller
	 * @param array $arguments Arguments for the action
	 * @return string
	 */
	public function render($controller, $action, array $arguments = array()) {
		$moduleArguments = array();
		$moduleArguments['SET']['function'] = $controller;
		$moduleArguments['CMD'] = $action;
		if (!empty($arguments)) {
			$moduleArguments['tx_scheduler'] = $arguments;
		}

		$uri = BackendUtility::getModuleUrl('system_txschedulerM1', $moduleArguments);
		$this->tag->addAttribute('href', $uri);
		$this->tag->setContent($this->renderChildren());
		$this->tag->forceClosingTag(TRUE);
		return $this->tag->render();
	}

}