<?php
namespace TYPO3\CMS\SysNote\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Kai Vogel <kai.vogel@speedprogs.de>, Speedprogs.de
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * SysNote model
 *
 * @author Kai Vogel <kai.vogel@speedprogs.de>
 */
class SysNote extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * @var \DateTime
	 */
	protected $creationDate;

	/**
	 * @var \DateTime
	 */
	protected $modificationDate;

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Model\BackendUser
	 */
	protected $author;

	/**
	 * @var string
	 */
	protected $subject;

	/**
	 * @var string
	 */
	protected $message;

	/**
	 * @var boolean
	 */
	protected $personal;

	/**
	 * @var integer
	 */
	protected $category;

	/**
	 * @return \DateTime $creationDate
	 */
	public function getCreationDate() {
		return $this->creationDate;
	}

	/**
	 * @param \DateTime $creationDate
	 * @return void
	 */
	public function setCreationDate($creationDate) {
		$this->creationDate = $creationDate;
	}

	/**
	 * @return \DateTime $modificationDate
	 */
	public function getModificationDate() {
		return $this->modificationDate;
	}

	/**
	 * @param \DateTime $modificationDate
	 * @return void
	 */
	public function setModificationDate($modificationDate) {
		$this->modificationDate = $modificationDate;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Domain\Model\BackendUser $author
	 */
	public function getAuthor() {
		return $this->author;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Domain\Model\BackendUser $author
	 * @return void
	 */
	public function setAuthor(\TYPO3\CMS\Extbase\Domain\Model\BackendUser $author) {
		$this->author = $author;
	}

	/**
	 * @return string $subject
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * @param string $subject
	 * @return void
	 */
	public function setSubject($subject) {
		$this->subject = $subject;
	}

	/**
	 * @return string $message
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * @param string $message
	 * @return void
	 */
	public function setMessage($message) {
		$this->message = $message;
	}

	/**
	 * @return boolean $personal
	 */
	public function getPersonal() {
		return $this->personal;
	}

	/**
	 * @param boolean $personal
	 * @return void
	 */
	public function setPersonal($personal) {
		$this->personal = $personal;
	}

	/**
	 * @return integer $category
	 */
	public function getCategory() {
		return $this->category;
	}

	/**
	 * @param integer $category
	 * @return void
	 */
	public function setCategory($category) {
		$this->category = $category;
	}

}
?>