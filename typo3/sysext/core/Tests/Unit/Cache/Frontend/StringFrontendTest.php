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
 * Testcase for the string cache frontend
 *
 * This file is a backport from FLOW3
 */
class StringFrontendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @test
     */
    public function setChecksIfTheIdentifierIsValid()
    {
        $cache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class, ['isValidEntryIdentifier'], [], '', false);
        $cache->expects($this->once())->method('isValidEntryIdentifier')->with('foo')->will($this->returnValue(false));
        $cache->set('foo', 'bar');
    }

    /**
     * @test
     */
    public function setPassesStringToBackend()
    {
        $theString = 'Just some value';
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('set')->with($this->equalTo('StringCacheTest'), $this->equalTo($theString));
        $cache = new \TYPO3\CMS\Core\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $cache->set('StringCacheTest', $theString);
    }

    /**
     * @test
     */
    public function setPassesLifetimeToBackend()
    {
        $theString = 'Just some value';
        $theLifetime = 1234;
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('set')->with($this->equalTo('StringCacheTest'), $this->equalTo($theString), $this->equalTo([]), $this->equalTo($theLifetime));
        $cache = new \TYPO3\CMS\Core\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $cache->set('StringCacheTest', $theString, [], $theLifetime);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception\InvalidDataException
     */
    public function setThrowsInvalidDataExceptionOnNonStringValues()
    {
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $cache = new \TYPO3\CMS\Core\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $cache->set('StringCacheTest', []);
    }

    /**
     * @test
     */
    public function getFetchesStringValueFromBackend()
    {
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('get')->will($this->returnValue('Just some value'));
        $cache = new \TYPO3\CMS\Core\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $this->assertEquals('Just some value', $cache->get('StringCacheTest'), 'The returned value was not the expected string.');
    }

    /**
     * @test
     */
    public function hasReturnsResultFromBackend()
    {
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('has')->with($this->equalTo('StringCacheTest'))->will($this->returnValue(true));
        $cache = new \TYPO3\CMS\Core\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $this->assertTrue($cache->has('StringCacheTest'), 'has() did not return TRUE.');
    }

    /**
     * @test
     */
    public function removeCallsBackend()
    {
        $cacheIdentifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('remove')->with($this->equalTo($cacheIdentifier))->will($this->returnValue(true));
        $cache = new \TYPO3\CMS\Core\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $this->assertTrue($cache->remove($cacheIdentifier), 'remove() did not return TRUE');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function getByTagRejectsInvalidTags()
    {
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\BackendInterface::class, [], [], '', false);
        $backend->expects($this->never())->method('getByTag');
        $cache = new \TYPO3\CMS\Core\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $cache->getByTag('SomeInvalid\\Tag');
    }

    /**
     * @test
     */
    public function getByTagCallsBackend()
    {
        $tag = 'sometag';
        $identifiers = ['one', 'two'];
        $entries = ['one value', 'two value'];
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('findIdentifiersByTag')->with($this->equalTo($tag))->will($this->returnValue($identifiers));
        $backend->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls('one value', 'two value'));
        $cache = new \TYPO3\CMS\Core\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $this->assertEquals($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
    }
}
