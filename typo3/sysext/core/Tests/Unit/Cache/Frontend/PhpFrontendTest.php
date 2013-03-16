<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache\Frontend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Testcase for the PHP source code cache frontend
 *
 * This file is a backport from FLOW3
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class PhpFrontendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setChecksIfTheIdentifierIsValid() {
		$cache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\StringFrontend', array('isValidEntryIdentifier'), array(), '', FALSE);
		$cache->expects($this->once())->method('isValidEntryIdentifier')->with('foo')->will($this->returnValue(FALSE));
		$cache->set('foo', 'bar');
	}

	/**
	 * @test
	 */
	public function setPassesPhpSourceCodeTagsAndLifetimeToBackend() {
		$originalSourceCode = 'return "hello world!";';
		$modifiedSourceCode = '<?php' . chr(10) . $originalSourceCode . chr(10) . '#';
		$mockBackend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\PhpCapableBackendInterface', array(), array(), '', FALSE);
		$mockBackend->expects($this->once())->method('set')->with('Foo-Bar', $modifiedSourceCode, array('tags'), 1234);
		$cache = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\Frontend\\PhpFrontend', 'PhpFrontend', $mockBackend);
		$cache->set('Foo-Bar', $originalSourceCode, array('tags'), 1234);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception\InvalidDataException
	 */
	public function setThrowsInvalidDataExceptionOnNonStringValues() {
		$cache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\PhpFrontend', array('dummy'), array(), '', FALSE);
		$cache->set('Foo-Bar', array());
	}

	/**
	 * @test
	 */
	public function requireOnceCallsTheBackendsRequireOnceMethod() {
		$mockBackend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\PhpCapableBackendInterface', array(), array(), '', FALSE);
		$mockBackend->expects($this->once())->method('requireOnce')->with('Foo-Bar')->will($this->returnValue('hello world!'));
		$cache = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\Frontend\\PhpFrontend', 'PhpFrontend', $mockBackend);
		$result = $cache->requireOnce('Foo-Bar');
		$this->assertSame('hello world!', $result);
	}

}

?>