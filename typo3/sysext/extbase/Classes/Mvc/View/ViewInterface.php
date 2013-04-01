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
 * Interface of a view
 *
 * @api
 */
interface ViewInterface {

	/**
	 * Sets the current controller context
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
	 * @return void
	 */
	public function setControllerContext(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext);

	/**
	 * Add a variable to the view data collection.
	 * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible
	 *
	 * @param string $key Key of variable
	 * @param mixed $value Value of object
	 * @return \TYPO3\CMS\Extbase\Mvc\View\ViewInterface an instance of $this, to enable chaining
	 * @api
	 */
	public function assign($key, $value);

	/**
	 * Add multiple variables to the view data collection
	 *
	 * @param array $values array in the format array(key1 => value1, key2 => value2)
	 * @return \TYPO3\CMS\Extbase\Mvc\View\ViewInterface an instance of $this, to enable chaining
	 * @api
	 */
	public function assignMultiple(array $values);

	/**
	 * Tells if the view implementation can render the view for the given context.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
	 * @return boolean TRUE if the view has something useful to display, otherwise FALSE
	 * @api
	 */
	public function canRender(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext);

	/**
	 * Renders the view
	 *
	 * @return string The rendered view
	 * @api
	 */
	public function render();

	/**
	 * Initializes this view.
	 *
	 * @return void
	 * @api
	 */
	public function initializeView();
}

?>