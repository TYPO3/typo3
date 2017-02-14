<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache\Backend;

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

use TYPO3\CMS\Core\Cache\Backend\ApcuBackend;
use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;

/**
 * Test case for the APCu cache backend.
 *
 * NOTE: If you want to execute these tests you need to enable apc in
 * cli context (apc.enable_cli = 1) and disable slam defense (apc.slam_defense = 0)
 */
class ApcuBackendTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * Set up
     */
    protected function setUp()
    {
        // APCu module is called apcu, but options are prefixed with apc
        if (!extension_loaded('apcu') || !(bool)ini_get('apc.enabled') || !(bool)ini_get('apc.enable_cli')) {
            $this->markTestSkipped('APCu extension was not available, or it was disabled for CLI.');
        }
        if ((bool)ini_get('apc.slam_defense')) {
            $this->markTestSkipped('This testcase can only be executed with apc.slam_defense = 0');
        }
    }

    /**
     * @test
     */
    public function setThrowsExceptionIfNoFrontEndHasBeenSet()
    {
        $backend = new ApcuBackend('Testing');
        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1232986118);
        $backend->set($identifier, $data);
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndCheckExistenceInCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
        $this->assertTrue($backend->has($identifier));
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndGetEntry()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
        $fetchedData = $backend->get($identifier);
        $this->assertEquals($data, $fetchedData);
    }

    /**
     * @test
     */
    public function itIsPossibleToRemoveEntryFromCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
        $backend->remove($identifier);
        $this->assertFalse($backend->has($identifier));
    }

    /**
     * @test
     */
    public function itIsPossibleToOverwriteAnEntryInTheCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
        $otherData = 'some other data';
        $backend->set($identifier, $otherData);
        $fetchedData = $backend->get($identifier);
        $this->assertEquals($otherData, $fetchedData);
    }

    /**
     * @test
     */
    public function findIdentifiersByTagFindsSetEntries()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $backend->set($identifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tag2']);
        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
        $this->assertEquals($identifier, $retrieved[0]);
        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
        $this->assertEquals($identifier, $retrieved[0]);
    }

    /**
     * @test
     */
    public function setRemovesTagsFromPreviousSet()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $backend->set($identifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tagX']);
        $backend->set($identifier, $data, ['UnitTestTag%tag3']);
        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tagX');
        $this->assertEquals([], $retrieved);
    }

    /**
     * @test
     */
    public function setCacheIsSettingIdentifierPrefixWithCacheIdentifier()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FrontendInterface $cacheMock */
        $cacheMock = $this->createMock(FrontendInterface::class);
        $cacheMock->expects($this->any())->method('getIdentifier')->will($this->returnValue(
            'testidentifier'
        ));

        /** @var $backendMock \PHPUnit_Framework_MockObject_MockObject|ApcuBackend */
        $backendMock = $this->getMockBuilder(ApcuBackend::class)
            ->setMethods(['setIdentifierPrefix', 'getCurrentUserData', 'getPathSite'])
            ->setConstructorArgs(['testcontext'])
            ->getMock();

        $backendMock->expects($this->once())->method('getCurrentUserData')->will(
            $this->returnValue(['name' => 'testname'])
        );

        $backendMock->expects($this->once())->method('getPathSite')->will(
            $this->returnValue('testpath')
        );

        $expectedIdentifier = 'TYPO3_' . GeneralUtility::shortMD5('testpath' . 'testname' . 'testcontext' . 'testidentifier', 12);
        $backendMock->expects($this->once())->method('setIdentifierPrefix')->with($expectedIdentifier);
        $backendMock->setCache($cacheMock);
    }

    /**
     * @test
     */
    public function hasReturnsFalseIfTheEntryDoesNotExist()
    {
        $backend = $this->setUpBackend();
        $identifier = $this->getUniqueId('NonExistingIdentifier');
        $this->assertFalse($backend->has($identifier));
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = $this->getUniqueId('NonExistingIdentifier');
        $this->assertFalse($backend->remove($identifier));
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag()
    {
        $backend = $this->setUpBackend();
        $data = 'some data' . microtime();
        $backend->set('BackendAPCUTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('BackendAPCUTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('BackendAPCUTest3', $data, ['UnitTestTag%test']);
        $backend->flushByTag('UnitTestTag%special');
        $this->assertTrue($backend->has('BackendAPCUTest1'));
        $this->assertFalse($backend->has('BackendAPCUTest2'));
        $this->assertTrue($backend->has('BackendAPCUTest3'));
    }

    /**
     * @test
     */
    public function flushByTagsRemovesCacheEntriesWithSpecifiedTags()
    {
        $backend = $this->setUpBackend();
        $data = 'some data' . microtime();
        $backend->set('BackendAPCUTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('BackendAPCUTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('BackendAPCUTest3', $data, ['UnitTestTag%test']);
        $backend->flushByTags(['UnitTestTag%special', 'UnitTestTag%boring']);
        $this->assertFalse($backend->has('BackendAPCUTest1'), 'BackendAPCTest1');
        $this->assertFalse($backend->has('BackendAPCUTest2'), 'BackendAPCTest2');
        $this->assertTrue($backend->has('BackendAPCUTest3'), 'BackendAPCTest3');
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
        $backend = $this->setUpBackend();
        $data = 'some data' . microtime();
        $backend->set('BackendAPCUTest1', $data);
        $backend->set('BackendAPCUTest2', $data);
        $backend->set('BackendAPCUTest3', $data);
        $backend->flush();
        $this->assertFalse($backend->has('BackendAPCUTest1'));
        $this->assertFalse($backend->has('BackendAPCUTest2'));
        $this->assertFalse($backend->has('BackendAPCUTest3'));
    }

    /**
     * @test
     */
    public function flushRemovesOnlyOwnEntries()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FrontendInterface $thisCache */
        $thisCache = $this->createMock(FrontendInterface::class);
        $thisCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thisCache'));
        $thisBackend = new ApcuBackend('Testing');
        $thisBackend->setCache($thisCache);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FrontendInterface $thatCache */
        $thatCache = $this->createMock(FrontendInterface::class);
        $thatCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thatCache'));
        $thatBackend = new ApcuBackend('Testing');
        $thatBackend->setCache($thatCache);
        $thisBackend->set('thisEntry', 'Hello');
        $thatBackend->set('thatEntry', 'World!');
        $thatBackend->flush();
        $this->assertEquals('Hello', $thisBackend->get('thisEntry'));
        $this->assertFalse($thatBackend->has('thatEntry'));
    }

    /**
     * Check if we can store ~5 MB of data
     *
     * @test
     */
    public function largeDataIsStored()
    {
        $backend = $this->setUpBackend();
        $data = str_repeat('abcde', 1024 * 1024);
        $identifier = $this->getUniqueId('tooLargeData');
        $backend->set($identifier, $data);
        $this->assertTrue($backend->has($identifier));
        $this->assertEquals($backend->get($identifier), $data);
    }

    /**
     * @test
     */
    public function setTagsOnlyOnceToIdentifier()
    {
        $identifier = $this->getUniqueId('MyIdentifier');
        $tags = ['UnitTestTag%test', 'UnitTestTag%boring'];

        $backend = $this->setUpBackend(true);
        $backend->_call('addIdentifierToTags', $identifier, $tags);
        $this->assertSame(
            $tags,
            $backend->_call('findTagsByIdentifier', $identifier)
        );

        $backend->_call('addIdentifierToTags', $identifier, $tags);
        $this->assertSame(
            $tags,
            $backend->_call('findTagsByIdentifier', $identifier)
        );
    }

    /**
     * Sets up the APCu backend used for testing
     *
     * @param bool $accessible TRUE if backend should be encapsulated in accessible proxy otherwise FALSE.
     * @return AccessibleObjectInterface|ApcuBackend
     */
    protected function setUpBackend($accessible = false)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FrontendInterface $cache */
        $cache = $this->createMock(FrontendInterface::class);
        if ($accessible) {
            $accessibleClassName = $this->buildAccessibleProxy(ApcuBackend::class);
            $backend = new $accessibleClassName('Testing');
        } else {
            $backend = new ApcuBackend('Testing');
        }
        $backend->setCache($cache);
        return $backend;
    }
}
