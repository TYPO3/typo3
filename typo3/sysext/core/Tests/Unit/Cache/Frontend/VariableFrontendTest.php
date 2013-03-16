<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache\Frontend;

/***************************************************************
*  Copyright notice
*
*  (c) 2009-2013 Ingo Renner <ingo@typo3.org>
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
 * Testcase for the variable cache frontend
 *
 * This file is a backport from FLOW3
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class VariableFrontendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @expectedException \InvalidArgumentException
	 * @test
	 */
	public function setChecksIfTheIdentifierIsValid() {
		$cache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\StringFrontend', array('isValidEntryIdentifier'), array(), '', FALSE);
		$cache->expects($this->once())->method('isValidEntryIdentifier')->with('foo')->will($this->returnValue(FALSE));
		$cache->set('foo', 'bar');
	}

	/**
	 * @test
	 */
	public function setPassesSerializedStringToBackend() {
		$theString = 'Just some value';
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('set')->with($this->equalTo('VariableCacheTest'), $this->equalTo(serialize($theString)));

		$cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
		$cache->set('VariableCacheTest', $theString);
	}

	/**
	 * @test
	 */
	public function setPassesSerializedArrayToBackend() {
		$theArray = array('Just some value', 'and another one.');
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('set')->with($this->equalTo('VariableCacheTest'), $this->equalTo(serialize($theArray)));

		$cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
		$cache->set('VariableCacheTest', $theArray);
	}

	/**
	 * @test
	 */
	public function setPassesLifetimeToBackend() {
		$theString = 'Just some value';
		$theLifetime = 1234;
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('set')->with($this->equalTo('VariableCacheTest'), $this->equalTo(serialize($theString)), $this->equalTo(array()), $this->equalTo($theLifetime));

		$cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
		$cache->set('VariableCacheTest', $theString, array(), $theLifetime);
	}

	/**
	 * @test
	 */
	public function setUsesIgBinarySerializeIfAvailable() {
		if (!extension_loaded('igbinary')) {
			$this->markTestSkipped('Cannot test igbinary support, because igbinary is not installed.');
		}

		$theString = 'Just some value';
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('set')->with($this->equalTo('VariableCacheTest'), $this->equalTo(igbinary_serialize($theString)));

		$cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
		$cache->initializeObject();
		$cache->set('VariableCacheTest', $theString);
	}

	/**
	 * @test
	 */
	public function getFetchesStringValueFromBackend() {
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('get')->will($this->returnValue(serialize('Just some value')));

		$cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
		$this->assertEquals('Just some value', $cache->get('VariableCacheTest'), 'The returned value was not the expected string.');
	}

	/**
	 * @test
	 */
	public function getFetchesArrayValueFromBackend() {
		$theArray = array('Just some value', 'and another one.');
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('get')->will($this->returnValue(serialize($theArray)));

		$cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
		$this->assertEquals($theArray, $cache->get('VariableCacheTest'), 'The returned value was not the expected unserialized array.');
	}

	/**
	 * @test
	 */
	public function getFetchesFalseBooleanValueFromBackend() {
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('get')->will($this->returnValue(serialize(FALSE)));

		$cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
		$this->assertFalse($cache->get('VariableCacheTest'), 'The returned value was not the FALSE.');
	}

	/**
	 * @test
	 */
	public function getUsesIgBinaryIfAvailable() {
		if (!extension_loaded('igbinary')) {
			$this->markTestSkipped('Cannot test igbinary support, because igbinary is not installed.');
		}

		$theArray = array('Just some value', 'and another one.');
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('get')->will($this->returnValue(igbinary_serialize($theArray)));

		$cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
		$cache->initializeObject();

		$this->assertEquals($theArray, $cache->get('VariableCacheTest'), 'The returned value was not the expected unserialized array.');
	}

	/**
	 * @test
	 */
	public function hasReturnsResultFromBackend() {
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('has')->with($this->equalTo('VariableCacheTest'))->will($this->returnValue(TRUE));

		$cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
		$this->assertTrue($cache->has('VariableCacheTest'), 'has() did not return TRUE.');
	}

	/**
	 * @test
	 */
	public function removeCallsBackend() {
		$cacheIdentifier = 'someCacheIdentifier';
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);

		$backend->expects($this->once())->method('remove')->with($this->equalTo($cacheIdentifier))->will($this->returnValue(TRUE));

		$cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
		$this->assertTrue($cache->remove($cacheIdentifier), 'remove() did not return TRUE');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function getByTagRejectsInvalidTags() {
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\BackendInterface', array(), array(), '', FALSE);
		$backend->expects($this->never())->method('getByTag');

		$cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
		$cache->getByTag('SomeInvalid\Tag');
	}

	/**
	 * @test
	 */
	public function getByTagCallsBackend() {
		$tag = 'sometag';
		$identifiers = array('one', 'two');
		$entries = array('one value', 'two value');
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);

		$backend->expects($this->once())->method('findIdentifiersByTag')->with($this->equalTo($tag))->will($this->returnValue($identifiers));
		$backend->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls(serialize('one value'), serialize('two value')));

		$cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
		$this->assertEquals($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
	}

	/**
	 * @test
	 */
	public function getByTagUsesIgBinaryIfAvailable() {
		if (!extension_loaded('igbinary')) {
			$this->markTestSkipped('Cannot test igbinary support, because igbinary is not installed.');
		}

		$tag = 'sometag';
		$identifiers = array('one', 'two');
		$entries = array('one value', 'two value');
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);

		$backend->expects($this->once())->method('findIdentifiersByTag')->with($this->equalTo($tag))->will($this->returnValue($identifiers));
		$backend->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls(igbinary_serialize('one value'), igbinary_serialize('two value')));

		$cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
		$cache->initializeObject();
		$this->assertEquals($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
	}
}

?>