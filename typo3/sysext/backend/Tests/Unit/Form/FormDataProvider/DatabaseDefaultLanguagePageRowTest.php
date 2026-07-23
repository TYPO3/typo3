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
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseDefaultLanguagePageRow;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Schema\FieldTypeFactory;
use TYPO3\CMS\Core\Schema\RelationMapBuilder;
use TYPO3\CMS\Core\Schema\SchemaCollection;
use TYPO3\CMS\Core\Schema\TcaSchemaBuilder;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DatabaseDefaultLanguagePageRowTest extends UnitTestCase
{
    private DatabaseDefaultLanguagePageRow&MockObject $subject;
    private SchemaCollection $schemaCollection;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TCA']['pages']['ctrl']['languageField'] = 'sys_language_uid';
        $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'] = 'l10n_parent';
        $GLOBALS['TCA']['pages']['columns']['l10n_parent'] = ['config' => ['type' => 'language']];
        $GLOBALS['TCA']['pages']['columns']['sys_language_uid'] = ['config' => ['type' => 'language']];
        $this->schemaCollection = $this->getSchemaCollection($GLOBALS['TCA']);
        $this->subject = $this->getMockBuilder(DatabaseDefaultLanguagePageRow::class)
            ->onlyMethods(['getDatabaseRow'])
            ->getMock();
    }

    #[Test]
    public function addDataDoesNotApplyToAnyNonPagesTable(): void
    {
        $input = [
            'tableName' => 'tx_doandroidsdreamofelectricsheep',
            'databaseRow' => [
                'uid' => 23,
                'l10n_parent' => 13,
                'sys_language_uid' => 23,
            ],
            'tcaSchemata' => $this->schemaCollection,
        ];
        $result = $this->subject->addData($input);

        self::assertArrayNotHasKey('defaultLanguagePageRow', $result);
    }

    #[Test]
    public function addDataDoesApplyToAPagesTableButNoChangeForDefaultLanguage(): void
    {
        $input = [
            'tableName' => 'pages',
            'databaseRow' => [
                'uid' => 23,
                'l10n_parent' => 0,
                'sys_language_uid' => 0,
            ],
            'tcaSchemata' => $this->schemaCollection,
        ];
        $result = $this->subject->addData($input);
        self::assertSame($input, $result);
    }

    #[Test]
    public function addDataDoesApplyToATranslatedPagesTable(): void
    {
        GeneralUtility::addInstance(TcaSchemaFactory::class, self::createStub(TcaSchemaFactory::class));

        $input = [
            'tableName' => 'pages',
            'databaseRow' => [
                'uid' => 23,
                'pid' => 1,
                'l10n_parent' => 13,
                'sys_language_uid' => 8,
            ],
            'tcaSchemata' => $this->schemaCollection,
        ];

        $defaultLanguagePageRow = [
            'uid' => 13,
            'pid' => 1,
            'sys_language_uid' => 0,
            'l10n_parent' => 0,
        ];

        $this->subject->expects($this->once())
            ->method('getDatabaseRow')
            ->with($input['tableName'], 13)
            ->willReturn($defaultLanguagePageRow);

        $result = $this->subject->addData($input);
        self::assertEquals($defaultLanguagePageRow, $result['defaultLanguagePageRow']);
    }

    private function getSchemaCollection(array $tca): SchemaCollection
    {
        $tcaSchemaFactory = new TcaSchemaBuilder(
            new RelationMapBuilder(self::createStub(FlexFormTools::class)),
            new FieldTypeFactory()
        );
        return $tcaSchemaFactory->buildFromStructure($tca);
    }
}
