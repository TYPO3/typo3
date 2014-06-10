<?php
namespace OliverHader\IrreTutorial\Domain\Model;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014 Oliver Hader <oliver.hader@typo3.org>
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
 * Hotel
 */
class Hotel extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * @var string
	 */
	protected $title = '';

	/**
	 * @lazy
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\OliverHader\IrreTutorial\Domain\Model\Offer>
	 */
	protected $offers = NULL;

	/**
	 * Initializes this object.
	 */
	public function __construct() {
		$this->offers = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * @return string $title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\OliverHader\IrreTutorial\Domain\Model\Offer>
	 */
	public function getOffers() {
		return $this->offers;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\OliverHader\IrreTutorial\Domain\Model\Offer> $offers
	 * @return void
	 */
	public function setOffers(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $offers) {
		$this->offers = $offers;
	}

}