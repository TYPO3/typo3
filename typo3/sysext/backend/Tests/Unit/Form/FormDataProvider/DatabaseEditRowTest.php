<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordException;
use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordWorkspaceDeletePlaceholderException;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow;
use TYPO3\CMS\Core\Schema\Field\FieldCollection;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DatabaseEditRowTest extends UnitTestCase
{
    protected DatabaseEditRow&MockObject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $tcaSchemaFactoryMock = $this->createMock(TcaSchemaFactory::class);
        $this->subject = $this->getMockBuilder(DatabaseEditRow::class)
            ->setConstructorArgs([$tcaSchemaFactoryMock])
            ->onlyMethods(['getDatabaseRow'])
            ->getMock();
    }

    #[Test]
    public function addDataRetrievesRecordInformationFromDatabase(): void
    {
        GeneralUtility::addInstance(TcaSchemaFactory::class, $this->createMock(TcaSchemaFactory::class));
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];
        $resultRow = [
            'uid' => 10,
            'pid' => 123,
        ];
        $this->subject->expects(self::once())->method('getDatabaseRow')->willReturn($resultRow);
        $result = $this->subject->addData($input);

        self::assertSame($resultRow, $result['databaseRow']);
    }

    #[Test]
    public function addDataThrowsExceptionIfRetrievedRowHasNoPid(): void
    {
        GeneralUtility::addInstance(TcaSchemaFactory::class, $this->createMock(TcaSchemaFactory::class));
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];
        $resultRow = [
            'uid' => 10,
        ];
        $this->subject->expects(self::once())->method('getDatabaseRow')->willReturn($resultRow);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1437663061);

        $this->subject->addData($input);
    }

    #[Test]
    public function addDataThrowsExceptionIfGivenUidIsNotPositive(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => -10,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1437656456);

        $this->subject->addData($input);
    }

    #[Test]
    public function addDataThrowsExceptionIfDatabaseFetchingReturnsNoRow(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];
        $this->subject->expects(self::once())->method('getDatabaseRow')->willReturn([]);

        $this->expectException(DatabaseRecordException::class);
        $this->expectExceptionCode(1437656081);

        $this->subject->addData($input);
    }

    #[Test]
    public function addDataThrowsExceptionDatabaseRecordExceptionWithAdditionalInformationSet(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];
        $this->subject->expects(self::once())->method('getDatabaseRow')->willReturn([]);

        try {
            $this->subject->addData($input);
        } catch (DatabaseRecordException $e) {
            self::assertSame('tt_content', $e->getTableName());
            self::assertSame(10, $e->getUid());
        }
    }

    #[Test]
    public function addDataThrowsWorkspaceDeletePlaceholderExceptionWithDeletePlaceholderRecord(): void
    {
        $this->expectException(DatabaseRecordWorkspaceDeletePlaceholderException::class);
        $this->expectExceptionCode(1608658396);
        $tcaSchemaFactoryMock = $this->createMock(TcaSchemaFactory::class);
        $tcaSchemaFactoryMock->method('has')->with('tt_content')->willReturn(true);
        $tcaSchemaFactoryMock->method('get')->with('tt_content')->willReturn(
            new TcaSchema('tt_content', new FieldCollection([]), ['versioningWS' => true])
        );
        $this->subject = $this->getMockBuilder(DatabaseEditRow::class)
            ->setConstructorArgs([$tcaSchemaFactoryMock])
            ->onlyMethods(['getDatabaseRow'])
            ->getMock();
        GeneralUtility::addInstance(TcaSchemaFactory::class, $this->createMock(TcaSchemaFactory::class));
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 10,
        ];
        $resultRow = [
            'uid' => 10,
            'pid' => 123,
            't3ver_state' => 2,
        ];
        $this->subject->expects(self::once())->method('getDatabaseRow')->willReturn($resultRow);
        $this->subject->addData($input);
    }

    #[Test]
    public function addDataSkipsDatabaseLookupIfDatabaseRowIsPopulated(): void
    {
        $virtualRow = [
            'uid' => 10,
            'pid' => 123,
            'title' => 'Title of the virtual record',
        ];
        $input = [
            'tableName' => 'virtual_table',
            'command' => 'edit',
            'vanillaUid' => 10,
            'databaseRow' => $virtualRow,
        ];
        $resultRow = $virtualRow;
        $this->subject->expects(self::never())->method('getDatabaseRow');

        $result = $this->subject->addData($input);

        self::assertSame($resultRow, $result['databaseRow']);
    }
}
