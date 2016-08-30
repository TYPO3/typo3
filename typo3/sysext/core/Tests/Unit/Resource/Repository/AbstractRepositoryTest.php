<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Repository;

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
 * Test case
 */
class AbstractRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Resource\AbstractRepository
     */
    protected $subject;

    protected $mockedDb;

    protected function createDatabaseMock()
    {
        $this->mockedDb = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $this->mockedDb;
    }

    protected function setUp()
    {
        $this->subject = $this->getMockForAbstractClass(\TYPO3\CMS\Core\Resource\AbstractRepository::class, [], '', false);
    }

    /**
     * @test
     */
    public function findByUidFailsIfUidIsString()
    {
        $this->setExpectedException('InvalidArgumentException', '', 1316779798);
        $this->subject->findByUid('asdf');
    }

    /**
     * @test
     */
    public function findByUidAcceptsNumericUidInString()
    {
        $this->createDatabaseMock();
        $this->mockedDb->expects($this->once())->method('exec_SELECTgetSingleRow')->with($this->anything(), $this->anything(), $this->stringContains('uid=' . 123))->will($this->returnValue(['uid' => 123]));
        $this->subject->findByUid('123');
    }

    /**
     * test runs on a concrete implementation of AbstractRepository
     * to ease the pain of testing a protected method. Feel free to improve.
     *
     * @test
     */
    public function getWhereClauseForEnabledFieldsIncludesDeletedCheckInBackend()
    {
        $GLOBALS['TCA'] = [
            'sys_file_storage' => [
                'ctrl' => [
                    'delete' => 'deleted',
                ],
            ],
        ];
        /** @var \TYPO3\CMS\Core\Resource\StorageRepository|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $storageRepositoryMock */
        $storageRepositoryMock = $this->getAccessibleMock(
            \TYPO3\CMS\Core\Resource\StorageRepository::class,
            ['dummy'],
            [],
            '',
            false
        );
        $result = $storageRepositoryMock->_call('getWhereClauseForEnabledFields');
        $this->assertContains('sys_file_storage.deleted=0', $result);
    }

    /**
     * test runs on a concrete implementation of AbstractRepository
     * to ease the pain of testing a protected method. Feel free to improve.
     *
     * @test
     */
    public function getWhereClauseForEnabledFieldsCallsSysPageForDeletedFlagInFrontend()
    {
        $GLOBALS['TSFE'] = new \stdClass();
        $sysPageMock = $this->getMock(\TYPO3\CMS\Frontend\Page\PageRepository::class);
        $GLOBALS['TSFE']->sys_page = $sysPageMock;
        $sysPageMock
            ->expects($this->once())
            ->method('deleteClause')
            ->with('sys_file_storage');
        $storageRepositoryMock = $this->getAccessibleMock(
            \TYPO3\CMS\Core\Resource\StorageRepository::class,
            ['getEnvironmentMode'],
            [],
            '',
            false
        );
        $storageRepositoryMock->expects($this->any())->method('getEnvironmentMode')->will($this->returnValue('FE'));
        $storageRepositoryMock->_call('getWhereClauseForEnabledFields');
    }
}
