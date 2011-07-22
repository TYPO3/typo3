<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Patrick Broens <patrick@patrickbroens.nl>
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
 * @category Model
 * @package TYPO3
 * @subpackage form
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @license http://www.gnu.org/copyleft/gpl.html
 * @version $Id$
 */
class tx_form_domain_model_content {

	/**
	 * The uid
	 *
	 * @var integer
	 */
	private $uid = 0;

	/**
	 * The page id
	 *
	 * @var integer
	 */
	private $pageId = 0;

	/**
	 * The configuration Typoscript
	 *
	 * @var array
	 */
	private $typoscript = array();

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct() {
	}

	/**
	 * Sets the uid
	 *
	 * @param integer $uid The uid
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setUid($uid) {
		$this->uid = (integer) $uid;
	}

	/**
	 * Returns the uid
	 *
	 * @return integer The uid
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getUid() {
		return $this->uid;
	}

	/**
	 * Sets the page id
	 *
	 * @param integer $pageId The page id
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setPageId($pageId) {
		$this->pageId = (integer) $pageId;
	}

	/**
	 * Returns the page id
	 *
	 * @return integer The page id
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getPageId() {
		return $this->pageId;
	}

	/**
	 * Sets the Typoscript configuration
	 *
	 * @param array $typoscript The Typoscript configuration
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setTyposcript(array $typoscript) {
		$this->typoscript = (array) $typoscript;
	}

	/**
	 * Returns the Typoscript configuration
	 *
	 * @return array The Typoscript configuration
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getTyposcript() {
		return $this->typoscript;
	}
}
?>