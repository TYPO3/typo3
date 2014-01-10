<?php
namespace TYPO3\CMS\Backend\Tests\Unit\View\BackendLayout;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Oliver Hader <oliver.hader@typo3.org>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
 * Testing backend layout representation.
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class BackendLayoutTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \UnexpectedValueException
	 */
	public function invalidIdentifierIsRecognizedOnCreation() {
		$identifier = uniqid('identifier__');
		$title = uniqid('title');
		$configuration = uniqid('configuration');
		new \TYPO3\CMS\Backend\View\BackendLayout\BackendLayout($identifier, $title, $configuration);
	}

	/**
	 * @test
	 */
	public function objectIsCreated() {
		$identifier = uniqid('identifier');
		$title = uniqid('title');
		$configuration = uniqid('configuration');
		$backendLayout = new \TYPO3\CMS\Backend\View\BackendLayout\BackendLayout($identifier, $title, $configuration);

		$this->assertEquals($identifier, $backendLayout->getIdentifier());
		$this->assertEquals($title, $backendLayout->getTitle());
		$this->assertEquals($configuration, $backendLayout->getConfiguration());
	}

}