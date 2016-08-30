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
 * Testcase for the variable cache frontend
 *
 * This file is a backport from FLOW3
 */
class VariableFrontendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
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
    public function setPassesSerializedStringToBackend()
    {
        $theString = 'Just some value';
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('set')->with($this->equalTo('VariableCacheTest'), $this->equalTo(serialize($theString)));

        $cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theString);
    }

    /**
     * @test
     */
    public function setPassesSerializedArrayToBackend()
    {
        $theArray = ['Just some value', 'and another one.'];
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('set')->with($this->equalTo('VariableCacheTest'), $this->equalTo(serialize($theArray)));

        $cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theArray);
    }

    /**
     * @test
     */
    public function setPassesLifetimeToBackend()
    {
        $theString = 'Just some value';
        $theLifetime = 1234;
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('set')->with($this->equalTo('VariableCacheTest'), $this->equalTo(serialize($theString)), $this->equalTo([]), $this->equalTo($theLifetime));

        $cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theString, [], $theLifetime);
    }

    /**
     * @test
     */
    public function setUsesIgBinarySerializeIfAvailable()
    {
        if (!extension_loaded('igbinary')) {
            $this->markTestSkipped('Cannot test igbinary support, because igbinary is not installed.');
        }

        $theString = 'Just some value';
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('set')->with($this->equalTo('VariableCacheTest'), $this->equalTo(igbinary_serialize($theString)));

        $cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $cache->initializeObject();
        $cache->set('VariableCacheTest', $theString);
    }

    /**
     * @test
     */
    public function getFetchesStringValueFromBackend()
    {
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('get')->will($this->returnValue(serialize('Just some value')));

        $cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $this->assertEquals('Just some value', $cache->get('VariableCacheTest'), 'The returned value was not the expected string.');
    }

    /**
     * @test
     */
    public function getFetchesArrayValueFromBackend()
    {
        $theArray = ['Just some value', 'and another one.'];
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('get')->will($this->returnValue(serialize($theArray)));

        $cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $this->assertEquals($theArray, $cache->get('VariableCacheTest'), 'The returned value was not the expected unserialized array.');
    }

    /**
     * @test
     */
    public function getFetchesFalseBooleanValueFromBackend()
    {
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('get')->will($this->returnValue(serialize(false)));

        $cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $this->assertFalse($cache->get('VariableCacheTest'), 'The returned value was not the FALSE.');
    }

    /**
     * @test
     */
    public function getUsesIgBinaryIfAvailable()
    {
        if (!extension_loaded('igbinary')) {
            $this->markTestSkipped('Cannot test igbinary support, because igbinary is not installed.');
        }

        $theArray = ['Just some value', 'and another one.'];
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('get')->will($this->returnValue(igbinary_serialize($theArray)));

        $cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $cache->initializeObject();

        $this->assertEquals($theArray, $cache->get('VariableCacheTest'), 'The returned value was not the expected unserialized array.');
    }

    /**
     * @test
     */
    public function hasReturnsResultFromBackend()
    {
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('has')->with($this->equalTo('VariableCacheTest'))->will($this->returnValue(true));

        $cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $this->assertTrue($cache->has('VariableCacheTest'), 'has() did not return TRUE.');
    }

    /**
     * @test
     */
    public function removeCallsBackend()
    {
        $cacheIdentifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);

        $backend->expects($this->once())->method('remove')->with($this->equalTo($cacheIdentifier))->will($this->returnValue(true));

        $cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
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

        $cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $cache->getByTag('SomeInvalid\Tag');
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
        $backend->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls(serialize('one value'), serialize('two value')));

        $cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $this->assertEquals($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
    }

    /**
     * @test
     */
    public function getByTagUsesIgBinaryIfAvailable()
    {
        if (!extension_loaded('igbinary')) {
            $this->markTestSkipped('Cannot test igbinary support, because igbinary is not installed.');
        }

        $tag = 'sometag';
        $identifiers = ['one', 'two'];
        $entries = ['one value', 'two value'];
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);

        $backend->expects($this->once())->method('findIdentifiersByTag')->with($this->equalTo($tag))->will($this->returnValue($identifiers));
        $backend->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls(igbinary_serialize('one value'), igbinary_serialize('two value')));

        $cache = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $cache->initializeObject();
        $this->assertEquals($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
    }
}
