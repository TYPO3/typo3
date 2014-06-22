<?php
namespace TYPO3\CMS\Form\Domain\Model;

/**
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
		$this->uid = (int)$uid;
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
		$this->pageId = (int)$pageId;
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
