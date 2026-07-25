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
use TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Schema\FieldTypeFactory;
use TYPO3\CMS\Core\Schema\RelationMapBuilder;
use TYPO3\CMS\Core\Schema\SchemaCollection;
use TYPO3\CMS\Core\Schema\TcaSchemaBuilder;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class InitializeProcessedTcaTest extends UnitTestCase
{
    private InitializeProcessedTca $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new InitializeProcessedTca($this->getTcaSchemaFactory());
    }

    #[Test]
    public function addDataSetsTableTcaFromGlobalsInResult(): void
    {
        $input = [
            'tableName' => 'aTable',
        ];
        $expected = [
            'columns' => [],
        ];
        $GLOBALS['TCA'][$input['tableName']] = $expected;
        $result = $this->subject->addData($input);
        self::assertEquals($expected, $result['processedTca']);
    }

    #[Test]
    public function addDataKeepsGivenProcessedTca(): void
    {
        $input = [
            'tableName' => 'aTable',
            'fullTca' => ['aTable' => ['columns' => ['afield' => []]]],
            'processedTca' => [
                'columns' => [
                    'afield' => [],
                ],
            ],
            'tcaSchemata' => new SchemaCollection([]),
        ];
        $expected = $input;
        self::assertEquals($expected, $this->subject->addData($input));
    }

    #[Test]
    public function addDataThrowsExceptionIfGlobalTableTcaIsNotSet(): void
    {
        $input = [
            'tableName' => 'aTable',
        ];
        $GLOBALS['TCA'] = [];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1437914223);

        $this->subject->addData($input);
    }

    #[Test]
    public function addDataThrowsExceptionIfGlobalTableTcaIsNotAnArray(): void
    {
        $input = [
            'tableName' => 'aTable',
        ];
        $GLOBALS['TCA'][$input['tableName']] = 'foo';
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1437914223);

        $this->subject->addData($input);
    }

    private function getTcaSchemaFactory(): TcaSchemaFactory
    {
        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->expects($this->atMost(PHP_INT_MAX))->method('has')->with(self::isString())->willReturn(false);
        return new TcaSchemaFactory(
            new TcaSchemaBuilder(
                new RelationMapBuilder(self::createStub(FlexFormTools::class)),
                new FieldTypeFactory(),
            ),
            '',
            $cacheMock
        );
    }
}
