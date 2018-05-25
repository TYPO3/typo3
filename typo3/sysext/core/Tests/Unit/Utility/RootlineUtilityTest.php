<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Tests\Unit\Utility;

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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RootlineUtilityTest extends UnitTestCase
{
    /**
     * @var RootlineUtility|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * @var PageRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $pageContextMock;

    /**
     * @throws \ReflectionException
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    protected function setUp(): void
    {
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('cache_rootline')->willReturn($cacheFrontendProphecy->reveal());

        $this->pageContextMock = $this->createMock(PageRepository::class);
        $this->subject = $this->getAccessibleMock(
            RootlineUtility::class,
            ['enrichWithRelationFields'],
            [1, '', $this->pageContextMock]
        );
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        RootlineUtility::purgeCaches();
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * Tests that $subsetCandidate is completely part of $superset
     * and keys match.
     *
     * @see (A ^ B) = A <=> A c B
     * @param array $subsetCandidate
     * @param array $superset
     */
    protected function assertIsSubset(array $subsetCandidate, array $superset): void
    {
        $this->assertSame($subsetCandidate, array_intersect_assoc($subsetCandidate, $superset));
    }

    /**
     * @test
     */
    public function isMountedPageWithoutMountPointsReturnsFalse(): void
    {
        $this->subject->__construct(1);
        $this->assertFalse($this->subject->isMountedPage());
    }

    /**
     * @test
     */
    public function isMountedPageWithMatchingMountPointParameterReturnsTrue(): void
    {
        $this->subject->__construct(1, '1-99');
        $this->assertTrue($this->subject->isMountedPage());
    }

    /**
     * @test
     */
    public function isMountedPageWithNonMatchingMountPointParameterReturnsFalse(): void
    {
        $this->subject->__construct(1, '99-99');
        $this->assertFalse($this->subject->isMountedPage());
    }

    /**
     * @test
     */
    public function processMountedPageWithNonMountedPageThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1343464100);

        $this->subject->__construct(1, '1-99');
        $this->subject->_call(
            'processMountedPage',
            ['uid' => 1],
            ['uid' => 99, 'doktype' => PageRepository::DOKTYPE_DEFAULT]
        );
    }

    /**
     * @test
     */
    public function processMountedPageWithMountedPageNotThrowsException(): void
    {
        $this->subject->__construct(1, '1-99');
        $this->assertNotEmpty($this->subject->_call(
            'processMountedPage',
            ['uid' => 1],
            ['uid' => 99, 'doktype' => PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1]
        ));
    }

    /**
     * @test
     */
    public function processMountedPageWithMountedPageAddsMountedFromParameter(): void
    {
        $this->subject->__construct(1, '1-99');
        $result = $this->subject->_call(
            'processMountedPage',
            ['uid' => 1],
            ['uid' => 99, 'doktype' => PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1]
        );
        $this->assertTrue(isset($result['_MOUNTED_FROM']));
        $this->assertSame(1, $result['_MOUNTED_FROM']);
    }

    /**
     * @test
     */
    public function processMountedPageWithMountedPageAddsMountPointParameterToReturnValue(): void
    {
        $this->subject->__construct(1, '1-99');
        $result = $this->subject->_call(
            'processMountedPage',
            ['uid' => 1],
            ['uid' => 99, 'doktype' => PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1]
        );
        $this->assertTrue(isset($result['_MP_PARAM']));
        $this->assertSame('1-99', $result['_MP_PARAM']);
    }

    /**
     * @test
     */
    public function processMountedPageForMountPageIsOverlayAddsMountOLParameter(): void
    {
        $this->subject->__construct(1, '1-99');
        $result = $this->subject->_call(
            'processMountedPage',
            ['uid' => 1],
            ['uid' => 99, 'doktype' => PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1, 'mount_pid_ol' => 1]
        );
        $this->assertTrue(isset($result['_MOUNT_OL']));
        $this->assertSame(true, $result['_MOUNT_OL']);
    }

    /**
     * @test
     */
    public function processMountedPageForMountPageIsOverlayAddsDataInformationAboutMountPage(): void
    {
        $this->subject->__construct(1, '1-99');
        $result = $this->subject->_call('processMountedPage', ['uid' => 1], [
            'uid' => 99,
            'doktype' => PageRepository::DOKTYPE_MOUNTPOINT,
            'mount_pid' => 1,
            'mount_pid_ol' => 1,
            'pid' => 5,
            'title' => 'TestCase'
        ]);
        $this->assertTrue(isset($result['_MOUNT_PAGE']));
        $this->assertSame(['uid' => 99, 'pid' => 5, 'title' => 'TestCase'], $result['_MOUNT_PAGE']);
    }

    /**
     * @test
     */
    public function processMountedPageForMountPageWithoutOverlayReplacesMountedPageWithMountPage(): void
    {
        $mountPointPageData = [
            'uid' => 99,
            'doktype' => PageRepository::DOKTYPE_MOUNTPOINT,
            'mount_pid' => 1,
            'mount_pid_ol' => 0
        ];
        $this->subject->__construct(1, '1-99');
        $result = $this->subject->_call('processMountedPage', ['uid' => 1], $mountPointPageData);
        $this->assertIsSubset($mountPointPageData, $result);
    }

    /**
     * @test
     */
    public function columnHasRelationToResolveDetectsGroupFieldAsLocal(): void
    {
        $this->assertFalse($this->subject->_call('columnHasRelationToResolve', [
            'type' => 'group'
        ]));
    }

    /**
     * @test
     */
    public function columnHasRelationToResolveDetectsGroupFieldWithMMAsRemote2(): void
    {
        $this->assertTrue($this->subject->_call('columnHasRelationToResolve', [
            'config' => [
                'type' => 'group',
                'MM' => 'tx_xyz'
            ]
        ]));
    }

    /**
     * @test
     */
    public function columnHasRelationToResolveDetectsInlineFieldAsLocal(): void
    {
        $this->assertFalse($this->subject->_call('columnHasRelationToResolve', [
            'config' => [
                'type' => 'inline'
            ]
        ]));
    }

    /**
     * @test
     */
    public function columnHasRelationToResolveDetectsInlineFieldWithForeignKeyAsRemote(): void
    {
        $this->assertTrue($this->subject->_call('columnHasRelationToResolve', [
            'config' => [
                'type' => 'inline',
                'foreign_field' => 'xyz'
            ]
        ]));
    }

    /**
     * @test
     */
    public function columnHasRelationToResolveDetectsInlineFieldWithFMMAsRemote(): void
    {
        $this->assertTrue($this->subject->_call('columnHasRelationToResolve', [
            'config' => [
                'type' => 'inline',
                'MM' => 'xyz'
            ]
        ]));
    }

    /**
     * @test
     */
    public function columnHasRelationToResolveDetectsSelectFieldAsLocal(): void
    {
        $this->assertFalse($this->subject->_call('columnHasRelationToResolve', [
            'config' => [
                'type' => 'select'
            ]
        ]));
    }

    /**
     * @test
     */
    public function columnHasRelationToResolveDetectsSelectFieldWithMMAsRemote(): void
    {
        $this->assertTrue($this->subject->_call('columnHasRelationToResolve', [
            'config' => [
                'type' => 'select',
                'MM' => 'xyz'
            ]
        ]));
    }

    /**
     * @test
     */
    public function getCacheIdentifierContainsAllContextParameters(): void
    {
        $this->pageContextMock->sys_language_uid = 8;
        $this->pageContextMock->versioningWorkspaceId = 15;
        $this->subject->__construct(42, '47-11', $this->pageContextMock);
        $this->assertSame('42_47-11_8_15', $this->subject->getCacheIdentifier());
        $this->subject->__construct(42, '47-11', $this->pageContextMock);
        $this->assertSame('42_47-11_8_15', $this->subject->getCacheIdentifier());
        $this->pageContextMock->versioningWorkspaceId = 0;
        $this->subject->__construct(42, '47-11', $this->pageContextMock);
        $this->assertSame('42_47-11_8_0', $this->subject->getCacheIdentifier());
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function getCacheIdentifierReturnsValidIdentifierWithCommasInMountPointParameter(): void
    {
        /** @var AbstractFrontend $cacheFrontendMock */
        $cacheFrontendMock = $this->getMockForAbstractClass(
            AbstractFrontend::class,
            [],
            '',
            false
        );
        $this->pageContextMock->sys_language_uid = 8;
        $this->pageContextMock->versioningWorkspaceId = 15;
        $this->subject->__construct(42, '47-11,48-12', $this->pageContextMock);
        $this->assertTrue($cacheFrontendMock->isValidEntryIdentifier($this->subject->getCacheIdentifier()));
    }
}
