<?php
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

use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\RootlineUtility
 */
class RootlineUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var RootlineUtility|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * @var \TYPO3\CMS\Frontend\Page\PageRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $pageContextMock;

    protected function setUp()
    {
        $this->pageContextMock = $this->getMock(\TYPO3\CMS\Frontend\Page\PageRepository::class);
        $this->subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Utility\RootlineUtility::class, ['enrichWithRelationFields'], [1, '', $this->pageContextMock]);
    }

    protected function tearDown()
    {
        parent::tearDown();
        RootlineUtility::purgeCaches();
    }

    /***
     *
     * 		UTILITY FUNCTIONS
     *
     */
    /**
     * Tests that $subsetCandidate is completely part of $superset
     * and keys match.
     *
     * @see (A ^ B) = A <=> A c B
     * @param array $subsetCandidate
     * @param array $superset
     */
    protected function assertIsSubset(array $subsetCandidate, array $superset)
    {
        $this->assertSame($subsetCandidate, array_intersect_assoc($subsetCandidate, $superset));
    }

    /***
     *
     * 		>TEST CASES
     *
     */
    /**
     * @test
     */
    public function isMountedPageWithoutMountPointsReturnsFalse()
    {
        $this->subject->__construct(1);
        $this->assertFalse($this->subject->isMountedPage());
    }

    /**
     * @test
     */
    public function isMountedPageWithMatchingMountPointParameterReturnsTrue()
    {
        $this->subject->__construct(1, '1-99');
        $this->assertTrue($this->subject->isMountedPage());
    }

    /**
     * @test
     */
    public function isMountedPageWithNonMatchingMountPointParameterReturnsFalse()
    {
        $this->subject->__construct(1, '99-99');
        $this->assertFalse($this->subject->isMountedPage());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function processMountedPageWithNonMountedPageThrowsException()
    {
        $this->subject->__construct(1, '1-99');
        $this->subject->_call('processMountedPage', ['uid' => 1], ['uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_DEFAULT]);
    }

    /**
     * @test
     */
    public function processMountedPageWithMountedPageNotThrowsException()
    {
        $this->subject->__construct(1, '1-99');
        $this->assertNotEmpty($this->subject->_call('processMountedPage', ['uid' => 1], ['uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1]));
    }

    /**
     * @test
     */
    public function processMountedPageWithMountedPageAddsMountedFromParameter()
    {
        $this->subject->__construct(1, '1-99');
        $result = $this->subject->_call('processMountedPage', ['uid' => 1], ['uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1]);
        $this->assertTrue(isset($result['_MOUNTED_FROM']));
        $this->assertSame(1, $result['_MOUNTED_FROM']);
    }

    /**
     * @test
     */
    public function processMountedPageWithMountedPageAddsMountPointParameterToReturnValue()
    {
        $this->subject->__construct(1, '1-99');
        $result = $this->subject->_call('processMountedPage', ['uid' => 1], ['uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1]);
        $this->assertTrue(isset($result['_MP_PARAM']));
        $this->assertSame('1-99', $result['_MP_PARAM']);
    }

    /**
     * @test
     */
    public function processMountedPageForMountPageIsOverlayAddsMountOLParameter()
    {
        $this->subject->__construct(1, '1-99');
        $result = $this->subject->_call('processMountedPage', ['uid' => 1], ['uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1, 'mount_pid_ol' => 1]);
        $this->assertTrue(isset($result['_MOUNT_OL']));
        $this->assertSame(true, $result['_MOUNT_OL']);
    }

    /**
     * @test
     */
    public function processMountedPageForMountPageIsOverlayAddsDataInformationAboutMountPage()
    {
        $this->subject->__construct(1, '1-99');
        $result = $this->subject->_call('processMountedPage', ['uid' => 1], ['uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1, 'mount_pid_ol' => 1, 'pid' => 5, 'title' => 'TestCase']);
        $this->assertTrue(isset($result['_MOUNT_PAGE']));
        $this->assertSame(['uid' => 99, 'pid' => 5, 'title' => 'TestCase'], $result['_MOUNT_PAGE']);
    }

    /**
     * @test
     */
    public function processMountedPageForMountPageWithoutOverlayReplacesMountedPageWithMountPage()
    {
        $mountPointPageData = ['uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1, 'mount_pid_ol' => 0];
        $this->subject->__construct(1, '1-99');
        $result = $this->subject->_call('processMountedPage', ['uid' => 1], $mountPointPageData);
        $this->assertIsSubset($mountPointPageData, $result);
    }

    /**
     * @test
     */
    public function columnHasRelationToResolveDetectsGroupFieldAsLocal()
    {
        $this->assertFalse($this->subject->_call('columnHasRelationToResolve', [
            'type' => 'group'
        ]));
    }

    /**
     * @test
     */
    public function columnHasRelationToResolveDetectsGroupFieldWithMMAsRemote2()
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
    public function columnHasRelationToResolveDetectsInlineFieldAsLocal()
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
    public function columnHasRelationToResolveDetectsInlineFieldWithForeignKeyAsRemote()
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
    public function columnHasRelationToResolveDetectsInlineFieldWithFMMAsRemote()
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
    public function columnHasRelationToResolveDetectsSelectFieldAsLocal()
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
    public function columnHasRelationToResolveDetectsSelectFieldWithMMAsRemote()
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
    public function getCacheIdentifierContainsAllContextParameters()
    {
        $this->pageContextMock->sys_language_uid = 8;
        $this->pageContextMock->versioningWorkspaceId = 15;
        $this->pageContextMock->versioningPreview = true;
        $this->subject->__construct(42, '47-11', $this->pageContextMock);
        $this->assertSame('42_47-11_8_15_1', $this->subject->getCacheIdentifier());
        $this->pageContextMock->versioningPreview = false;
        $this->subject->__construct(42, '47-11', $this->pageContextMock);
        $this->assertSame('42_47-11_8_15_0', $this->subject->getCacheIdentifier());
        $this->pageContextMock->versioningWorkspaceId = 0;
        $this->subject->__construct(42, '47-11', $this->pageContextMock);
        $this->assertSame('42_47-11_8_0_0', $this->subject->getCacheIdentifier());
    }

    /**
     * @test
     */
    public function getCacheIdentifierReturnsValidIdentifierWithCommasInMountPointParameter()
    {
        /** @var \TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend $cacheFrontendMock */
        $cacheFrontendMock = $this->getMockForAbstractClass(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], '', false);
        $this->pageContextMock->sys_language_uid = 8;
        $this->pageContextMock->versioningWorkspaceId = 15;
        $this->pageContextMock->versioningPreview = true;
        $this->subject->__construct(42, '47-11,48-12', $this->pageContextMock);
        $this->assertTrue($cacheFrontendMock->isValidEntryIdentifier($this->subject->getCacheIdentifier()));
    }

    /**
     * @test
     */
    public function getRecordArrayFetchesTranslationWhenLanguageIdIsSet()
    {
        $pageData = [
            'uid' => 1,
            'title' => 'Original',
        ];
        $pageDataTranslated = [
            'uid' => 1,
            'title' => 'Translated',
            '_PAGES_OVERLAY_UID' => '2',
        ];

        $this->subject
            ->expects($this->any())
            ->method('enrichWithRelationFields')
            ->with(2, $pageDataTranslated)
            ->will($this->returnArgument(1));

        $databaseConnectionMock = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $databaseConnectionMock
            ->expects($this->once())
            ->method('exec_SELECTgetSingleRow')
            ->will(
                $this->returnValue($pageData)
            );
        $this->subject->_set('databaseConnection',
            $databaseConnectionMock
        );

        $this->pageContextMock
                ->expects($this->any())
                ->method('getPageOverlay')
                ->will($this->returnValue($pageDataTranslated));

        $this->subject->_set('languageUid', 1);
        $this->assertSame($pageDataTranslated, $this->subject->_call('getRecordArray', 1));
    }

    /**
     * @test
     */
    public function enrichWithRelationFieldsCreatesWhereClauseForDisabledField()
    {
        $mockDatabaseConnection = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, ['exec_SELECTgetRows'], [], '', false);
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Utility\RootlineUtility::class, ['columnHasRelationToResolve'], [1, '', $this->pageContextMock]);
        $subject->_set('databaseConnection', $mockDatabaseConnection);
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields'] = '';
        $foreign_table = $this->getUniqueId('foreign_table');
        $foreign_field = $this->getUniqueId('foreign_field');
        $GLOBALS['TCA'][$foreign_table]['ctrl']['enablecolumns']['disabled'] = $this->getUniqueId('disabled');
        $GLOBALS['TCA']['pages']['columns'] = [
            'test' => [
                'config' => [
                    'foreign_table' => $foreign_table,
                    'foreign_field' => $foreign_field
                ]
            ]
        ];
        $expected = [
            $foreign_field . ' = 0',
            $foreign_table . '.' . $GLOBALS['TCA'][$foreign_table]['ctrl']['enablecolumns']['disabled'] . ' = 0'
        ];
        $this->pageContextMock->expects($this->once())->method('deleteClause')->will($this->returnValue(''));
        $mockDatabaseConnection->expects(
            $this->once())->
            method('exec_SELECTgetRows')->
            with('uid', $foreign_table, implode(' AND ', $expected), '', '', '', '')->
            // the return value does not matter much, it is only here to prevent error messages from further code execution
            will($this->returnValue(['uid' => 17])
        );
        $subject->expects($this->once())->method('columnHasRelationToResolve')->will($this->returnValue(true));
        $subject->_call('enrichWithRelationFields', 17, []);
    }
}
