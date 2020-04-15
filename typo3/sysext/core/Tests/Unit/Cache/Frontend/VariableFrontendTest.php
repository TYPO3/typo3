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
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class VariableFrontendTest extends UnitTestCase
{
    /**
     * @test
     */
    public function setChecksIfTheIdentifierIsValid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1233058264);

        $cache = $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['isValidEntryIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects(self::once())->method('isValidEntryIdentifier')->with('foo')->willReturn(false);
        $cache->set('foo', 'bar');
    }

    /**
     * @test
     */
    public function setPassesSerializedStringToBackend()
    {
        $theString = 'Just some value';
        $backend = $this->getMockBuilder(AbstractBackend::class)
            ->setMethods(['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects(self::once())->method('set')->with(self::equalTo('VariableCacheTest'), self::equalTo(serialize($theString)));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theString);
    }

    /**
     * @test
     */
    public function setPassesSerializedArrayToBackend()
    {
        $theArray = ['Just some value', 'and another one.'];
        $backend = $this->getMockBuilder(AbstractBackend::class)
            ->setMethods(['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects(self::once())->method('set')->with(self::equalTo('VariableCacheTest'), self::equalTo(serialize($theArray)));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theArray);
    }

    /**
     * @test
     */
    public function setPassesLifetimeToBackend()
    {
        $theString = 'Just some value';
        $theLifetime = 1234;
        $backend = $this->getMockBuilder(AbstractBackend::class)
            ->setMethods(['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects(self::once())->method('set')->with(self::equalTo('VariableCacheTest'), self::equalTo(serialize($theString)), self::equalTo([]), self::equalTo($theLifetime));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theString, [], $theLifetime);
    }

    /**
     * @test
     */
    public function getFetchesStringValueFromBackend()
    {
        $backend = $this->getMockBuilder(AbstractBackend::class)
            ->setMethods(['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects(self::once())->method('get')->willReturn(serialize('Just some value'));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertEquals('Just some value', $cache->get('VariableCacheTest'), 'The returned value was not the expected string.');
    }

    /**
     * @test
     */
    public function getFetchesArrayValueFromBackend()
    {
        $theArray = ['Just some value', 'and another one.'];
        $backend = $this->getMockBuilder(AbstractBackend::class)
            ->setMethods(['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects(self::once())->method('get')->willReturn(serialize($theArray));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertEquals($theArray, $cache->get('VariableCacheTest'), 'The returned value was not the expected unserialized array.');
    }

    /**
     * @test
     */
    public function getFetchesFalseBooleanValueFromBackend()
    {
        $backend = $this->getMockBuilder(AbstractBackend::class)
            ->setMethods(['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects(self::once())->method('get')->willReturn(serialize(false));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertFalse($cache->get('VariableCacheTest'), 'The returned value was not the FALSE.');
    }

    /**
     * @test
     */
    public function hasReturnsResultFromBackend()
    {
        $backend = $this->getMockBuilder(AbstractBackend::class)
            ->setMethods(['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects(self::once())->method('has')->with(self::equalTo('VariableCacheTest'))->willReturn(true);

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertTrue($cache->has('VariableCacheTest'), 'has() did not return TRUE.');
    }

    /**
     * @test
     */
    public function removeCallsBackend()
    {
        $cacheIdentifier = 'someCacheIdentifier';
        $backend = $this->getMockBuilder(AbstractBackend::class)
            ->setMethods(['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
            ->disableOriginalConstructor()
            ->getMock();

        $backend->expects(self::once())->method('remove')->with(self::equalTo($cacheIdentifier))->willReturn(true);

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertTrue($cache->remove($cacheIdentifier), 'remove() did not return TRUE');
    }
}
