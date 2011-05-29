<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Ingo Renner <ingo@typo3.org>
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
 * Testcase for the abstract cache frontend
 *
 * This file is a backport from FLOW3
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tests
 */
class t3lib_cache_frontend_AbstractFrontendTest extends tx_phpunit_testcase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function theConstructorAcceptsValidIdentifiers() {
		$mockBackend = $this->getMock('t3lib_cache_backend_AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'findIdentifiersByTags', 'flush', 'flushByTag', 'flushByTags', 'collectGarbage'), array(), '', FALSE);
		foreach (array('x', 'someValue', '123fivesixseveneight', 'some&', 'ab_cd%', rawurlencode('resource://some/äöü$&% sadf'), str_repeat('x', 250)) as $identifier) {
			$abstractCache = $this->getMock('t3lib_cache_frontend_StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag', 'flush', 'flushByTag', 'collectGarbage'), array($identifier, $mockBackend));
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function theConstructorRejectsInvalidIdentifiers() {
		$mockBackend = $this->getMock('t3lib_cache_backend_AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'findIdentifiersByTags', 'flush', 'flushByTag', 'flushByTags', 'collectGarbage'), array(), '', FALSE);
		foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#') as $identifier) {
			try {
				$abstractCache = $this->getMock('t3lib_cache_frontend_StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag', 'flush', 'flushByTag', 'collectGarbage'), array($identifier, $mockBackend));
				$this->fail('Identifier "' . $identifier . '" was not rejected.');
			} catch (\InvalidArgumentException $exception) {
			}
		}
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushCallsBackend() {
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('t3lib_cache_backend_AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'findIdentifiersByTags', 'flush', 'flushByTag', 'flushByTags', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('flush');

		$cache = $this->getMock('t3lib_cache_frontend_StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		$cache->flush();
	}


	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushByTagRejectsInvalidTags() {
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('t3lib_cache_backend_Backend', array(), array(), '', FALSE);
		$backend->expects($this->never())->method('flushByTag');

		$cache = $this->getMock('t3lib_cache_frontend_StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		$cache->flushByTag('SomeInvalid\Tag');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushByTagCallsBackend() {
		$tag = 'sometag';
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('t3lib_cache_backend_AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'findIdentifiersByTags', 'flush', 'flushByTag', 'flushByTags', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('flushByTag')->with($tag);

		$cache = $this->getMock('t3lib_cache_frontend_StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		$cache->flushByTag($tag);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function collectGarbageCallsBackend() {
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('t3lib_cache_backend_AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'findIdentifiersByTags', 'flush', 'flushByTag', 'flushByTags', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('collectGarbage');

		$cache = $this->getMock('t3lib_cache_frontend_StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		$cache->collectGarbage();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function getClassTagRendersTagWhichCanBeUsedToTagACacheEntryWithACertainClass() {
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('t3lib_cache_backend_AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'findIdentifiersByTags', 'flush', 'flushByTag', 'flushByTags', 'collectGarbage'), array(), '', FALSE);

		$cache = $this->getMock('t3lib_cache_frontend_StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		$this->assertEquals('%CLASS%F3_Foo_Bar_Baz', t3lib_cache_Manager::getClassTag('F3\Foo\Bar\Baz'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function invalidEntryIdentifiersAreRecognizedAsInvalid() {
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('t3lib_cache_backend_AbstractBackend', array(), array(), '', FALSE);
		$cache = $this->getMock('t3lib_cache_frontend_StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#') as $entryIdentifier) {
			$this->assertFalse($cache->isValidEntryIdentifier($entryIdentifier), 'Invalid identifier "' . $entryIdentifier . '" was not rejected.');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function validEntryIdentifiersAreRecognizedAsValid() {
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('t3lib_cache_backend_AbstractBackend', array(), array(), '', FALSE);
		$cache = $this->getMock('t3lib_cache_frontend_StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		foreach (array('_', 'abcdef', 'foo', 'bar123', '3some', '_bl_a', 'some&', 'one%TWO', str_repeat('x', 250)) as $entryIdentifier) {
			$this->assertTrue($cache->isValidEntryIdentifier($entryIdentifier), 'Valid identifier "' . $entryIdentifier . '" was not accepted.');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function invalidTagsAreRecognizedAsInvalid() {
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('t3lib_cache_backend_AbstractBackend', array(), array(), '', FALSE);
		$cache = $this->getMock('t3lib_cache_frontend_StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#') as $tag) {
			$this->assertFalse($cache->isValidTag($tag), 'Invalid tag "' . $tag . '" was not rejected.');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function validTagsAreRecognizedAsValid() {
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('t3lib_cache_backend_AbstractBackend', array(), array(), '', FALSE);
		$cache = $this->getMock('t3lib_cache_frontend_StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		foreach (array('abcdef', 'foo-bar', 'foo_baar', 'bar123', '3some', 'file%Thing', 'some&', '%x%', str_repeat('x', 250)) as $tag) {
			$this->assertTrue($cache->isValidTag($tag), 'Valid tag "' . $tag . '" was not accepted.');
		}
	}
}

?>