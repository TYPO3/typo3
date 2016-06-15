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
        $mockBackend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class)
            ->setMethods(array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'))
            ->disableOriginalConstructor()
            ->getMock();
        foreach (array('x', 'someValue', '123fivesixseveneight', 'some&', 'ab_cd%', rawurlencode('resource://some/äöü$&% sadf'), str_repeat('x', 250)) as $identifier) {
            $abstractCache = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class)
                ->setMethods(array('__construct', 'get', 'set', 'has', 'remove', 'getByTag', 'flush', 'flushByTag', 'collectGarbage'))
                ->setConstructorArgs(array($identifier, $mockBackend))
                ->getMock();
        }
    }

    /**
     * @test
     */
    public function theConstructorRejectsInvalidIdentifiers()
    {
        $mockBackend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class)
            ->setMethods(array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'))
            ->disableOriginalConstructor()
            ->getMock();
        foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#') as $identifier) {
            try {
                $abstractCache = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class)
                    ->setMethods(array('__construct', 'get', 'set', 'has', 'remove', 'getByTag', 'flush', 'flushByTag', 'collectGarbage'))
                    ->setConstructorArgs(array($identifier, $mockBackend))
                    ->getMock();
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
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class)
            ->setMethods(array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'))
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects($this->once())->method('flush');
        $cache = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class)
            ->setMethods(array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'))
            ->setConstructorArgs(array($identifier, $backend))
            ->getMock();
        $cache->flush();
    }

    /**
     * @test
     */
    public function flushByTagRejectsInvalidTags()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1233057359);

        $identifier = 'someCacheIdentifier';
        $backend = $this->createMock(\TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface::class);
        $backend->expects($this->never())->method('flushByTag');
        $cache = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class)
            ->setMethods(array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'))
            ->setConstructorArgs(array($identifier, $backend))
            ->getMock();
        $cache->flushByTag('SomeInvalid\\Tag');
    }

    /**
     * @test
     */
    public function flushByTagCallsBackendIfItIsATaggableBackend()
    {
        $tag = 'sometag';
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface::class)
            ->setMethods(array('setCache', 'get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'))
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects($this->once())->method('flushByTag')->with($tag);
        $cache = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class)
            ->setMethods(array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'))
            ->setConstructorArgs(array($identifier, $backend))
            ->getMock();
        $cache->flushByTag($tag);
    }

    /**
     * @test
     */
    public function collectGarbageCallsBackend()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class)
            ->setMethods(array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'))
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects($this->once())->method('collectGarbage');
        $cache = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class)
            ->setMethods(array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'))
            ->setConstructorArgs(array($identifier, $backend))
            ->getMock();
        $cache->collectGarbage();
    }

    /**
     * @test
     */
    public function invalidEntryIdentifiersAreRecognizedAsInvalid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->createMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class);
        $cache = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class)
            ->setMethods(array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'))
            ->setConstructorArgs(array($identifier, $backend))
            ->getMock();
        foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#') as $entryIdentifier) {
            $this->assertFalse($cache->isValidEntryIdentifier($entryIdentifier), 'Invalid identifier "' . $entryIdentifier . '" was not rejected.');
        }
    }

    /**
     * @test
     */
    public function validEntryIdentifiersAreRecognizedAsValid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->createMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class);
        $cache = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class)
            ->setMethods(array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'))
            ->setConstructorArgs(array($identifier, $backend))
            ->getMock();
        foreach (array('_', 'abcdef', 'foo', 'bar123', '3some', '_bl_a', 'some&', 'one%TWO', str_repeat('x', 250)) as $entryIdentifier) {
            $this->assertTrue($cache->isValidEntryIdentifier($entryIdentifier), 'Valid identifier "' . $entryIdentifier . '" was not accepted.');
        }
    }

    /**
     * @test
     */
    public function invalidTagsAreRecognizedAsInvalid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->createMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class);
        $cache = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class)
            ->setMethods(array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'))
            ->setConstructorArgs(array($identifier, $backend))
            ->getMock();
        foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#') as $tag) {
            $this->assertFalse($cache->isValidTag($tag), 'Invalid tag "' . $tag . '" was not rejected.');
        }
    }

    /**
     * @test
     */
    public function validTagsAreRecognizedAsValid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->createMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class);
        $cache = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class)
            ->setMethods(array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'))
            ->setConstructorArgs(array($identifier, $backend))
            ->getMock();
        foreach (array('abcdef', 'foo-bar', 'foo_baar', 'bar123', '3some', 'file%Thing', 'some&', '%x%', str_repeat('x', 250)) as $tag) {
            $this->assertTrue($cache->isValidTag($tag), 'Valid tag "' . $tag . '" was not accepted.');
        }
    }
}
