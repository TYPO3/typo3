<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Driver;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
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

require_once dirname(__FILE__) . '/../BaseTestCase.php';

/**
 * Test case for the abstract driver.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class AbstractDriverTest extends \TYPO3\CMS\Core\Tests\Unit\Resource\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Resource\Driver\AbstractDriver
	 */
	protected $fixture;

	/**
	 * @return \TYPO3\CMS\Core\Resource\Driver\AbstractDriver
	 */
	protected function createDriverFixture() {
		return $this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\Driver\\AbstractDriver', array(), '', FALSE);
	}

	public function filenameValidationDataProvider() {
		return array(
			'all-lowercase filename with extension' => array(
				'testfile.txt',
				TRUE
			),
			'regular filename with mixed case and extension' => array(
				'someFilename.jpg',
				TRUE
			),
			'filename with german umlauts' => array(
				'anÜmläütTestfile.jpg',
				TRUE
			),
			'filename with double extension' => array(
				'someCompressedFile.tar.gz',
				TRUE
			),
			'filename with dash' => array(
				'foo-bar',
				TRUE
			),
			'filename with number' => array(
				'some23Number',
				TRUE
			),
			'filename with whitespace' => array(
				'some whitespace',
				TRUE
			),
			'filename with tab' => array(
				'some' . TAB . 'tag',
				TRUE
			),
			'filename with carriage return' => array(
				'some' . CR . 'CarriageReturn',
				FALSE
			),
			'filename with linefeed' => array(
				'some' . LF . 'Linefeed',
				FALSE
			),
			'filename with leading slash' => array(
				'/invalidAsFilename',
				FALSE
			),
			'filename with null character' => array(
				'someFile' . chr(0) . 'name',
				FALSE
			)
		);
	}

	/**
	 * @test
	 * @dataProvider filenameValidationDataProvider
	 */
	public function filenamesAreCorrectlyValidated($filename, $expectedResult) {
		$fixture = $this->createDriverFixture(array());
		$result = $fixture->isValidFilename($filename);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @test
	 */
	public function getFolderCorrectlySetsFolderName() {
		$identifier = '/someFolder/someSubfolder/';
		$fixture = $this->createDriverFixture(array());
		$fixture->setStorage($this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE));
		$mockedFactory = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
		$mockedFactory->expects($this->once())->method('createFolderObject')->with($this->anything(), $this->anything(), 'someSubfolder');
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory', $mockedFactory);
		$fixture->getFolder($identifier);
	}

}

?>