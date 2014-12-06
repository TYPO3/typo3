<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource;

/*
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

		$mockedStorageForParent = $this->getMock(\TYPO3\CMS\Core\Resource\ResourceStorage::class, array(), array(), '', FALSE);

		/** @var \TYPO3\CMS\Core\Resource\AbstractFile $parentFolderFixture */
		$parentFolderFixture = $this->getMockForAbstractClass(\TYPO3\CMS\Core\Resource\AbstractFile::class);
		$parentFolderFixture->setIdentifier($parentIdentifier)->setStorage($mockedStorageForParent);

		$mockedStorage = $this->getMock(\TYPO3\CMS\Core\Resource\ResourceStorage::class, array('getFolderIdentifierFromFileIdentifier', 'getFolder'), array(), '', FALSE);
		$mockedStorage->expects($this->once())->method('getFolderIdentifierFromFileIdentifier')->with($currentIdentifier)->will($this->returnValue($parentIdentifier));
		$mockedStorage->expects($this->once())->method('getFolder')->with($parentIdentifier)->will($this->returnValue($parentFolderFixture));

		/** @var \TYPO3\CMS\Core\Resource\AbstractFile $currentFolderFixture */
		$currentFolderFixture = $this->getMockForAbstractClass(\TYPO3\CMS\Core\Resource\AbstractFile::class);
		$currentFolderFixture->setIdentifier($currentIdentifier)->setStorage($mockedStorage);

		$this->assertSame($parentFolderFixture, $currentFolderFixture->getParentFolder());
	}
}
