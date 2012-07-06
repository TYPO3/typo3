<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012
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
 * Category Model
 *
 * @package TYPO3
 * @subpackage sys_category
 */
class T3lib_Category_Domain_Model_Category extends Tx_Extbase_DomainObject_AbstractValueObject {

	/**
	 * @var string
	 * @validate notEmpty
	 */
	protected $title;

	/**
	 * @var string
	 * @validate notEmpty
	 */
	protected $description;

	/**
	 * @var t3lib_category_Domain_Model_Category
	 */
	protected $parent;

	/**
	 * @var DateTime
	 */
	protected $crdate;

	/**
	 * @var DateTime
	 */
	protected $tstamp;

	/**
	 * @var integer
	 */
	protected $sysLanguageUid;

	/**
	 * @var DateTime
	 */
	protected $starttime;

	/**
	 * @var DateTime
	 */
	protected $endtime;

	/**
	 * @var boolean
	 */
	protected $hidden;

	/**
	 * @var boolean
	 */
	protected $deleted;

	/**
	 * @var integer
	 */
	protected $cruserId;

	/**
	 * Get creation date
	 *
	 * @return DateTime
	 */
	public function getCrdate() {
		return $this->crdate;
	}

	/**
	 * Set Creation Date
	 *
	 * @param DateTime $crdate crdate
	 * @return void
	 */
	public function setCrdate($crdate) {
		$this->crdate = $crdate;
	}

	/**
	 * Get Tstamp
	 *
	 * @return DateTime
	 */
	public function getTstamp() {
		return $this->tstamp;
	}

	/**
	 * Set tstamp
	 *
	 * @param DateTime $tstamp tstamp
	 * @return void
	 */
	public function setTstamp($tstamp) {
		$this->tstamp = $tstamp;
	}

	/**
	 * Get starttime
	 *
	 * @return int
	 */
	public function getStarttime() {
		return $this->starttime;
	}

	/**
	 * Set starttime
	 *
	 * @param DateTime $starttime starttime
	 * @return void
	 */
	public function setStarttime($starttime) {
		$this->starttime = $starttime;
	}

	/**
	 * Get Endtime
	 *
	 * @return DateTime
	 */
	public function getEndtime() {
		return $this->endtime;
	}

	/**
	 * Set Endtime
	 *
	 * @param DateTime $endtime endttime
	 * @return void
	 */
	public function setEndtime($endtime) {
		$this->endtime = $endtime;
	}

	/**
	 * Get sys language
	 *
	 * @return integer
	 */
	public function getSysLanguageUid() {
		return $this->sysLanguageUid;
	}

	/**
	 * Set sys language
	 *
	 * @param integer $sysLanguageUid language uid
	 * @return void
	 */
	public function setSysLanguageUid($sysLanguageUid) {
		$this->sysLanguageUid = $sysLanguageUid;
	}

	/**
	 * Get category title
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Set category title
	 *
	 * @param string $title title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Get description
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Set description
	 *
	 * @param string $description description
	 * @return void
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * @return t3lib_category_Domain_Model_Category
	 */
	public function getParent() {
		return $this->parent;
	}
	/**
	 * @param t3lib_category_Domain_Model_Category $parent
	 */
	public function setParent($parent) {
		$this->parent = $parent;
	}

	/**
	 * Get hidden flag
	 *
	 * @return integer
	 */
	public function getHidden() {
		return $this->hidden;
	}

	/**
	 * Set hidden flag
	 *
	 * @param integer $hidden hidden flag
	 * @return void
	 */
	public function setHidden($hidden) {
		$this->hidden = $hidden;
	}

	/**
	 * Get deleted flag
	 *
	 * @return integer
	 */
	public function getDeleted() {
		return $this->deleted;
	}

	/**
	 * Set deleted flag
	 *
	 * @param integer $deleted deleted flag
	 * @return void
	 */
	public function setDeleted($deleted) {
		$this->deleted = $deleted;
	}
	/**
	 * @return int
	 */
	public function getCruserId() {
		return $this->cruserId;
	}
	/**
	 * @param int $cruserId
	 */
	public function setCruserId($cruserId) {
		$this->cruserId = $cruserId;
	}
}
?>
