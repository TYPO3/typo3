<?php
namespace TYPO3\CMS\Extbase\Mvc\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 * An abstract View
 *
 * @api
 */
abstract class AbstractView implements \TYPO3\CMS\Extbase\Mvc\View\ViewInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
	 * @api
	 */
	protected $controllerContext;

	/**
	 * View variables and their values
	 *
	 * @var array
	 * @see assign()
	 */
	protected $variables = array();

	/**
	 * Sets the current controller context
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
	 * @return void
	 */
	public function setControllerContext(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	/**
	 * Add a variable to $this->viewData.
	 * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible
	 *
	 * @param string $key Key of variable
	 * @param object $value Value of object
	 * @return \TYPO3\CMS\Extbase\Mvc\View\AbstractView an instance of $this, to enable chaining
	 * @api
	 */
	public function assign($key, $value) {
		$this->variables[$key] = $value;
		return $this;
	}

	/**
	 * Add multiple variables to $this->viewData.
	 *
	 * @param array $values array in the format array(key1 => value1, key2 => value2).
	 * @return \TYPO3\CMS\Extbase\Mvc\View\AbstractView an instance of $this, to enable chaining
	 * @api
	 */
	public function assignMultiple(array $values) {
		foreach ($values as $key => $value) {
			$this->assign($key, $value);
		}
		return $this;
	}

	/**
	 * Tells if the view implementation can render the view for the given context.
	 *
	 * By default we assume that the view implementation can handle all kinds of
	 * contexts. Override this method if that is not the case.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
	 * @return boolean TRUE if the view has something useful to display, otherwise FALSE
	 * @api
	 */
	public function canRender(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext) {
		return TRUE;
	}

	/**
	 * Initializes this view.
	 *
	 * Override this method for initializing your concrete view implementation.
	 *
	 * @return void
	 * @api
	 */
	public function initializeView() {
	}
}

?>