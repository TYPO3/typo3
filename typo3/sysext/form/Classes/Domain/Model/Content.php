<?php
namespace TYPO3\CMS\Form\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Patrick Broens <patrick@patrickbroens.nl>
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
 * Content domain model
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class Content {

	/**
	 * The uid
	 *
	 * @var integer
	 */
	protected $uid = 0;

	/**
	 * The page id
	 *
	 * @var integer
	 */
	protected $pageId = 0;

	/**
	 * The configuration Typoscript
	 *
	 * @var array
	 */
	protected $typoscript = array();

	/**
	 * Sets the uid
	 *
	 * @param integer $uid The uid
	 * @return void
	 */
	public function setUid($uid) {
		$this->uid = (int) $uid;
	}

	/**
	 * Returns the uid
	 *
	 * @return integer The uid
	 */
	public function getUid() {
		return $this->uid;
	}

	/**
	 * Sets the page id
	 *
	 * @param integer $pageId The page id
	 * @return void
	 */
	public function setPageId($pageId) {
		$this->pageId = (int) $pageId;
	}

	/**
	 * Returns the page id
	 *
	 * @return integer The page id
	 */
	public function getPageId() {
		return $this->pageId;
	}

	/**
	 * Sets the Typoscript configuration
	 *
	 * @param array $typoscript The Typoscript configuration
	 * @return void
	 */
	public function setTyposcript(array $typoscript) {
		$this->typoscript = (array) $typoscript;
	}

	/**
	 * Returns the Typoscript configuration
	 *
	 * @return array The Typoscript configuration
	 */
	public function getTyposcript() {
		return $this->typoscript;
	}

}

?>