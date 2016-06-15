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
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;

/**
 * Testcase for the string cache frontend
 *
 * This file is a backport from FLOW3
 */
class StringFrontendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function setChecksIfTheIdentifierIsValid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1233057566);

        $cache = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class)
            ->setMethods(array('isValidEntryIdentifier'))
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects($this->once())->method('isValidEntryIdentifier')->with('foo')->will($this->returnValue(false));
        $cache->set('foo', 'bar');
    }

    /**
     * @test
     */
    public function setPassesStringToBackend()
    {
        $theString = 'Just some value';
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class)
            ->setMethods(array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'))
            ->disableOriginalConstructor()
            ->getMock();
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
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class)
            ->setMethods(array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'))
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects($this->once())->method('set')->with($this->equalTo('StringCacheTest'), $this->equalTo($theString), $this->equalTo(array()), $this->equalTo($theLifetime));
        $cache = new \TYPO3\CMS\Core\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $cache->set('StringCacheTest', $theString, array(), $theLifetime);
    }

    /**
     * @test
     */
    public function setThrowsInvalidDataExceptionOnNonStringValues()
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionCode(1222808333);

        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class)
            ->setMethods(array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'))
            ->disableOriginalConstructor()
            ->getMock();
        $cache = new \TYPO3\CMS\Core\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $cache->set('StringCacheTest', array());
    }

    /**
     * @test
     */
    public function getFetchesStringValueFromBackend()
    {
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class)
            ->setMethods(array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'))
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects($this->once())->method('get')->will($this->returnValue('Just some value'));
        $cache = new \TYPO3\CMS\Core\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $this->assertEquals('Just some value', $cache->get('StringCacheTest'), 'The returned value was not the expected string.');
    }

    /**
     * @test
     */
    public function hasReturnsResultFromBackend()
    {
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class)
            ->setMethods(array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'))
            ->disableOriginalConstructor()
            ->getMock();
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
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class)
            ->setMethods(array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'))
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects($this->once())->method('remove')->with($this->equalTo($cacheIdentifier))->will($this->returnValue(true));
        $cache = new \TYPO3\CMS\Core\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $this->assertTrue($cache->remove($cacheIdentifier), 'remove() did not return TRUE');
    }

    /**
     * @test
     */
    public function getByTagRejectsInvalidTags()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1233057772);

        $backend = $this->createMock(\TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface::class);
        $cache = new \TYPO3\CMS\Core\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $cache->getByTag('SomeInvalid\\Tag');
    }

    /**
     * @test
     */
    public function getByTagCallsBackend()
    {
        $tag = 'sometag';
        $identifiers = array('one', 'two');
        $entries = array('one value', 'two value');
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class)
            ->setMethods(array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'))
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects($this->once())->method('findIdentifiersByTag')->with($this->equalTo($tag))->will($this->returnValue($identifiers));
        $backend->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls('one value', 'two value'));
        $cache = new \TYPO3\CMS\Core\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $this->assertEquals($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
    }
}
