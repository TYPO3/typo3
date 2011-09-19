<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Oliver Hader <oliver.hader@typo3.org>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * The form wizard load view
 *
 * @category View
 * @package TYPO3
 * @subpackage form
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
abstract class tx_form_View_Wizard_Abstract {
	/**
	 * Is the referenced record available
	 *
	 * @var boolean TRUE if available, FALSE if not
	 */
	protected $recordIsAvailable = FALSE;

	/**
	 * @var tx_form_Domain_Repository_Content
	 */
	protected $repository;

	/**
	 * Creates the object and calls the initialize() method.
	 *
	 * @param tx_form_Domain_Repository_Content $repository
	 */
	public function __construct(tx_form_Domain_Repository_Content $repository) {
		$this->setRepository($repository);
	}

	/**
	 * Sets the content repository to be used.
	 *
	 * @param tx_form_Domain_Repository_Content $repository
	 * @return void
	 */
	public function setRepository(tx_form_Domain_Repository_Content $repository) {
		$this->repository = $repository;
	}

	/**
	 * The main render method
	 *
	 * @return void
	 */
	abstract public function render();
}
?>