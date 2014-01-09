<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Steffen Müller <typo3@t3node.com>
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
 * Testcase for the abstract file class of the TYPO3 FAL
 *
 * @author Steffen Müller <typo3@t3node.com>
 */
class AbstractFileTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getParentFolderGetsParentFolderFromStorage() {
		$parentIdentifier = '/parent/';
		$currentIdentifier = '/parent/current/';

		$mockedStorageForParent = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);

		/** @var \TYPO3\CMS\Core\Resource\AbstractFile $parentFolderFixture */
		$parentFolderFixture = $this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\AbstractFile');
		$parentFolderFixture->setIdentifier($parentIdentifier)->setStorage($mockedStorageForParent);

		$mockedStorage = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array('getFolderIdentifierFromFileIdentifier', 'getFolder'), array(), '', FALSE);
		$mockedStorage->expects($this->once())->method('getFolderIdentifierFromFileIdentifier')->with($currentIdentifier)->will($this->returnValue($parentIdentifier));
		$mockedStorage->expects($this->once())->method('getFolder')->with($parentIdentifier)->will($this->returnValue($parentFolderFixture));

		/** @var \TYPO3\CMS\Core\Resource\AbstractFile $currentFolderFixture */
		$currentFolderFixture = $this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\AbstractFile');
		$currentFolderFixture->setIdentifier($currentIdentifier)->setStorage($mockedStorage);

		$this->assertSame($parentFolderFixture, $currentFolderFixture->getParentFolder());
	}
}
