<?php

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

namespace TYPO3\CMS\Core\Tests\Unit\Cache\Frontend;

use TYPO3\CMS\Core\Cache\Backend\AbstractBackend;
use TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the abstract cache frontend
 */
class AbstractFrontendTest extends UnitTestCase
{
    /**
     * @test
     */
    public function theConstructorAcceptsValidIdentifiers()
    {
        $mockBackend = $this->getMockBuilder(AbstractBackend::class)
            ->setMethods(['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
            ->disableOriginalConstructor()
            ->getMock();
        foreach (['x', 'someValue', '123fivesixseveneight', 'some&', 'ab_cd%', rawurlencode('resource://some/äöü$&% sadf'), str_repeat('x', 250)] as $identifier) {
            $this->getMockBuilder(VariableFrontend::class)
                ->setMethods(['__construct', 'get', 'set', 'has', 'remove', 'flush', 'flushByTag', 'collectGarbage'])
                ->setConstructorArgs([$identifier, $mockBackend])
                ->getMock();
        }
    }

    /**
     * @test
     */
    public function theConstructorRejectsInvalidIdentifiers()
    {
        $mockBackend = $this->getMockBuilder(AbstractBackend::class)
            ->setMethods(['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
            ->disableOriginalConstructor()
            ->getMock();
        foreach (['', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#'] as $identifier) {
            try {
                $this->getMockBuilder(VariableFrontend::class)
                    ->setMethods(['__construct', 'get', 'set', 'has', 'remove', 'flush', 'flushByTag', 'collectGarbage'])
                    ->setConstructorArgs([$identifier, $mockBackend])
                    ->getMock();
                self::fail('Identifier "' . $identifier . '" was not rejected.');
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
        $backend = $this->getMockBuilder(AbstractBackend::class)
            ->setMethods(['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects(self::once())->method('flush');
        $cache = $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove'])
            ->setConstructorArgs([$identifier, $backend])
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
        $backend = $this->createMock(TaggableBackendInterface::class);
        $backend->expects(self::never())->method('flushByTag');
        $cache = $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove'])
            ->setConstructorArgs([$identifier, $backend])
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
        $backend = $this->getMockBuilder(TaggableBackendInterface::class)
            ->setMethods(['setCache', 'get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'flushByTags', 'collectGarbage'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects(self::once())->method('flushByTag')->with($tag);
        $cache = $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove'])
            ->setConstructorArgs([$identifier, $backend])
            ->getMock();
        $cache->flushByTag($tag);
    }

    /**
     * @test
     */
    public function flushByTagsCallsBackendIfItIsATaggableBackend()
    {
        $tag = 'sometag';
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMockBuilder(TaggableBackendInterface::class)
            ->setMethods(['setCache', 'get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'flushByTags', 'collectGarbage'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects(self::once())->method('flushByTags')->with([$tag]);
        $cache = $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove'])
            ->setConstructorArgs([$identifier, $backend])
            ->getMock();
        $cache->flushByTags([$tag]);
    }

    /**
     * @test
     */
    public function collectGarbageCallsBackend()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMockBuilder(AbstractBackend::class)
            ->setMethods(['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects(self::once())->method('collectGarbage');
        $cache = $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove'])
            ->setConstructorArgs([$identifier, $backend])
            ->getMock();
        $cache->collectGarbage();
    }

    /**
     * @test
     */
    public function invalidEntryIdentifiersAreRecognizedAsInvalid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->createMock(AbstractBackend::class);
        $cache = $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove'])
            ->setConstructorArgs([$identifier, $backend])
            ->getMock();
        foreach (['', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#'] as $entryIdentifier) {
            self::assertFalse($cache->isValidEntryIdentifier($entryIdentifier), 'Invalid identifier "' . $entryIdentifier . '" was not rejected.');
        }
    }

    /**
     * @test
     */
    public function validEntryIdentifiersAreRecognizedAsValid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->createMock(AbstractBackend::class);
        $cache = $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove'])
            ->setConstructorArgs([$identifier, $backend])
            ->getMock();
        foreach (['_', 'abcdef', 'foo', 'bar123', '3some', '_bl_a', 'some&', 'one%TWO', str_repeat('x', 250)] as $entryIdentifier) {
            self::assertTrue($cache->isValidEntryIdentifier($entryIdentifier), 'Valid identifier "' . $entryIdentifier . '" was not accepted.');
        }
    }

    /**
     * @test
     */
    public function invalidTagsAreRecognizedAsInvalid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->createMock(AbstractBackend::class);
        $cache = $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove'])
            ->setConstructorArgs([$identifier, $backend])
            ->getMock();
        foreach (['', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#'] as $tag) {
            self::assertFalse($cache->isValidTag($tag), 'Invalid tag "' . $tag . '" was not rejected.');
        }
    }

    /**
     * @test
     */
    public function validTagsAreRecognizedAsValid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->createMock(AbstractBackend::class);
        $cache = $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove'])
            ->setConstructorArgs([$identifier, $backend])
            ->getMock();
        foreach (['abcdef', 'foo-bar', 'foo_baar', 'bar123', '3some', 'file%Thing', 'some&', '%x%', str_repeat('x', 250)] as $tag) {
            self::assertTrue($cache->isValidTag($tag), 'Valid tag "' . $tag . '" was not accepted.');
        }
    }
}
