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

namespace TYPO3\CMS\Core\Tests\Unit\Utility\File;

use Doctrine\DBAL\Driver\Statement;
use Prophecy\Argument;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility
 */
class ExtendedFileUtilityTest extends UnitTestCase
{
    /**
     * Sets up this testcase
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->getMockBuilder(LanguageService::class)
            ->disableOriginalConstructor()
            ->setMethods(['sL'])
            ->getMock();
    }

    /**
     * @test
     */
    public function folderHasFilesInUseReturnsTrueIfItHasFiles()
    {
        $fileUid = 1;
        $file = $this->getMockBuilder(File::class)
            ->setMethods(['getUid'])
            ->disableOriginalConstructor()
            ->getMock();
        $file->expects(self::once())->method('getUid')->willReturn($fileUid);

        $folder = $this->getMockBuilder(Folder::class)
            ->setMethods(['getFiles'])
            ->disableOriginalConstructor()
            ->getMock();
        $folder->expects(self::once())
            ->method('getFiles')->with(0, 0, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, true)
            ->willReturn(
                [$file]
            );

        /** @var \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility $subject */
        $subject = $this->getMockBuilder(ExtendedFileUtility::class)
            ->setMethods(['addFlashMessage'])
            ->getMock();

        // prophetizing the DB query
        $expressionBuilderProphet = $this->prophesize(ExpressionBuilder::class);
        $expressionBuilderProphet->eq(Argument::cetera())->willReturn('1 = 1');
        $expressionBuilderProphet->neq(Argument::cetera())->willReturn('1 != 1');
        $expressionBuilderProphet->in(Argument::cetera())->willReturn('uid IN (1)');
        $databaseStatementProphet = $this->prophesize(Statement::class);
        $databaseStatementProphet->fetchColumn(Argument::cetera())->willReturn(1);
        $queryBuilderProphet = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphet->getRestrictions()->willReturn(GeneralUtility::makeInstance(DefaultRestrictionContainer::class));
        $queryBuilderProphet->count(Argument::cetera())->willReturn($queryBuilderProphet);
        $queryBuilderProphet->from(Argument::cetera())->willReturn($queryBuilderProphet);
        $queryBuilderProphet->where(Argument::cetera())->willReturn($queryBuilderProphet);
        $queryBuilderProphet->createNamedParameter(Argument::cetera())->willReturn(Argument::type('string'));
        $queryBuilderProphet->execute()->willReturn($databaseStatementProphet);
        $queryBuilderProphet->expr()->willReturn($expressionBuilderProphet->reveal());
        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getQueryBuilderForTable(Argument::cetera())->willReturn($queryBuilderProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

        $GLOBALS['LANG']->expects(self::at(0))->method('sL')
            ->with('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:message.description.folderNotDeletedHasFilesWithReferences')
            ->willReturn('folderNotDeletedHasFilesWithReferences');
        $GLOBALS['LANG']->expects(self::at(1))->method('sL')
            ->with('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:message.header.folderNotDeletedHasFilesWithReferences')
            ->willReturn('folderNotDeletedHasFilesWithReferences');

        $result = $subject->folderHasFilesInUse($folder);
        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function folderHasFilesInUseReturnsFalseIfItHasNoFiles()
    {
        $folder = $this->getMockBuilder(Folder::class)
            ->setMethods(['getFiles'])
            ->disableOriginalConstructor()
            ->getMock();
        $folder->expects(self::once())->method('getFiles')->with(0, 0, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, true)->willReturn(
            []
        );

        /** @var \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility $subject */
        $subject = $this->getMockBuilder(ExtendedFileUtility::class)
            ->setMethods(['addFlashMessage'])
            ->getMock();
        self::assertFalse($subject->folderHasFilesInUse($folder));
    }
}
