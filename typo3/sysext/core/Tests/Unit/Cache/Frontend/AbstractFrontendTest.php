<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache\Frontend;

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
 * Testcase for the abstract cache frontend
 *
 * This file is a backport from FLOW3
 */
class AbstractFrontendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function theConstructorAcceptsValidIdentifiers()
    {
        $mockBackend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        foreach (['x', 'someValue', '123fivesixseveneight', 'some&', 'ab_cd%', rawurlencode('resource://some/äöü$&% sadf'), str_repeat('x', 250)] as $identifier) {
            $abstractCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class, ['__construct', 'get', 'set', 'has', 'remove', 'getByTag', 'flush', 'flushByTag', 'collectGarbage'], [$identifier, $mockBackend]);
        }
    }

    /**
     * @test
     */
    public function theConstructorRejectsInvalidIdentifiers()
    {
        $mockBackend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        foreach (['', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#'] as $identifier) {
            try {
                $abstractCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class, ['__construct', 'get', 'set', 'has', 'remove', 'getByTag', 'flush', 'flushByTag', 'collectGarbage'], [$identifier, $mockBackend]);
                $this->fail('Identifier "' . $identifier . '" was not rejected.');
            } catch (\InvalidArgumentException $exception) {
            }
        }
    }

    /**
     * @test
     */
    public function flushCallsBackend()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('flush');
        $cache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class, ['__construct', 'get', 'set', 'has', 'remove', 'getByTag'], [$identifier, $backend]);
        $cache->flush();
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function flushByTagRejectsInvalidTags()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\BackendInterface::class, [], [], '', false);
        $backend->expects($this->never())->method('flushByTag');
        $cache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class, ['__construct', 'get', 'set', 'has', 'remove', 'getByTag'], [$identifier, $backend]);
        $cache->flushByTag('SomeInvalid\\Tag');
    }

    /**
     * @test
     */
    public function flushByTagCallsBackendIfItIsATaggableBackend()
    {
        $tag = 'sometag';
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface::class, ['setCache', 'get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('flushByTag')->with($tag);
        $cache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class, ['__construct', 'get', 'set', 'has', 'remove', 'getByTag'], [$identifier, $backend]);
        $cache->flushByTag($tag);
    }

    /**
     * @test
     */
    public function collectGarbageCallsBackend()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('collectGarbage');
        $cache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class, ['__construct', 'get', 'set', 'has', 'remove', 'getByTag'], [$identifier, $backend]);
        $cache->collectGarbage();
    }

    /**
     * @test
     */
    public function invalidEntryIdentifiersAreRecognizedAsInvalid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, [], [], '', false);
        $cache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class, ['__construct', 'get', 'set', 'has', 'remove', 'getByTag'], [$identifier, $backend]);
        foreach (['', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#'] as $entryIdentifier) {
            $this->assertFalse($cache->isValidEntryIdentifier($entryIdentifier), 'Invalid identifier "' . $entryIdentifier . '" was not rejected.');
        }
    }

    /**
     * @test
     */
    public function validEntryIdentifiersAreRecognizedAsValid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, [], [], '', false);
        $cache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class, ['__construct', 'get', 'set', 'has', 'remove', 'getByTag'], [$identifier, $backend]);
        foreach (['_', 'abcdef', 'foo', 'bar123', '3some', '_bl_a', 'some&', 'one%TWO', str_repeat('x', 250)] as $entryIdentifier) {
            $this->assertTrue($cache->isValidEntryIdentifier($entryIdentifier), 'Valid identifier "' . $entryIdentifier . '" was not accepted.');
        }
    }

    /**
     * @test
     */
    public function invalidTagsAreRecognizedAsInvalid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, [], [], '', false);
        $cache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class, ['__construct', 'get', 'set', 'has', 'remove', 'getByTag'], [$identifier, $backend]);
        foreach (['', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#'] as $tag) {
            $this->assertFalse($cache->isValidTag($tag), 'Invalid tag "' . $tag . '" was not rejected.');
        }
    }

    /**
     * @test
     */
    public function validTagsAreRecognizedAsValid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, [], [], '', false);
        $cache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class, ['__construct', 'get', 'set', 'has', 'remove', 'getByTag'], [$identifier, $backend]);
        foreach (['abcdef', 'foo-bar', 'foo_baar', 'bar123', '3some', 'file%Thing', 'some&', '%x%', str_repeat('x', 250)] as $tag) {
            $this->assertTrue($cache->isValidTag($tag), 'Valid tag "' . $tag . '" was not accepted.');
        }
    }
}
