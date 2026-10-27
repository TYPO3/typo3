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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\ContextualRecordEditController;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ContextualRecordEditControllerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/backend/Tests/Functional/Controller/Fixtures/Extensions/test_persist_null',
    ];
    private ContextualRecordEditController $subject;

    private NormalizedParams $normalizedParams;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/test_persist_null.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->subject = $this->get(ContextualRecordEditController::class);
        $this->normalizedParams = new NormalizedParams([], [], '', '');
    }

    public static function saveNullValuesViaControlDataProvider(): \Generator
    {
        yield 'field "input_null": save value without setting control' => [
            'expectedField' => 'input_null',
            'dataInput' => 'testValue',
            'setControl' => false,
            'controlInput' => 0,
            'expectedValue' => 'testValue',
        ];
        yield 'field "input_null": save value with setting checkbox to keep value' => [
            'expectedField' => 'input_null',
            'dataInput' => 'testValue',
            'setControl' => true,
            'controlInput' => 1,
            'expectedValue' => 'testValue',
        ];
        yield 'field "input_null": save value without setting checkbox' => [
            'expectedField' => 'input_null',
            'dataInput' => 'testValue',
            'setControl' => true,
            'controlInput' => 0,
            'expectedValue' => null,
        ];
        yield 'field "number_null": save value 1 without setting control' => [
            'expectedField' => 'number_null',
            'dataInput' => 1,
            'setControl' => false,
            'controlInput' => 0,
            'expectedValue' => 1,
        ];
        yield 'field "number_null": save value 1 with setting checkbox to keep value' => [
            'expectedField' => 'number_null',
            'dataInput' => 1,
            'setControl' => true,
            'controlInput' => 1,
            'expectedValue' => 1,
        ];
        yield 'field "number_null": save value 1 without setting checkbox' => [
            'expectedField' => 'number_null',
            'dataInput' => 1,
            'setControl' => true,
            'controlInput' => 0,
            'expectedValue' => null,
        ];
        yield 'field "number_null": save value 0 without setting control' => [
            'expectedField' => 'number_null',
            'dataInput' => 0,
            'setControl' => false,
            'controlInput' => 0,
            'expectedValue' => 0,
        ];
        yield 'field "number_null": save value 0 with setting checkbox to keep value' => [
            'expectedField' => 'number_null',
            'dataInput' => 0,
            'setControl' => true,
            'controlInput' => 1,
            'expectedValue' => 0,
        ];
        yield 'field "number_null": save value 0 without setting checkbox' => [
            'expectedField' => 'number_null',
            'dataInput' => 0,
            'setControl' => true,
            'controlInput' => 0,
            'expectedValue' => null,
        ];
        yield 'field "number_null_float": save value 1.23 without setting control' => [
            'expectedField' => 'number_null_float',
            'dataInput' => 1.23,
            'setControl' => false,
            'controlInput' => 0,
            'expectedValue' => '1.23',
        ];
        yield 'field "number_null_float": save value 1.23 with setting checkbox to keep value' => [
            'expectedField' => 'number_null_float',
            'dataInput' => 1.23,
            'setControl' => true,
            'controlInput' => 1,
            'expectedValue' => '1.23',
        ];
        yield 'field "number_null_float": save value 1.23 without setting checkbox' => [
            'expectedField' => 'number_null_float',
            'dataInput' => 1.23,
            'setControl' => true,
            'controlInput' => 0,
            'expectedValue' => null,
        ];
    }

    #[Test]
    #[DataProvider('saveNullValuesViaControlDataProvider')]
    public function saveNullValuesViaControlForExistingRecord(string $expectedField, mixed $dataInput, bool $setControl, int $controlInput, mixed $expectedValue): void
    {
        $request = new ServerRequest('https://www.example.com/', 'POST')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $queryParams = [
            'edit' => [
                'test_persist_null' => [
                    '1' => 'edit',
                ],
            ],
        ];
        $parsedBody = [
            'data' => [
                'test_persist_null' => [
                    '1' => [
                        $expectedField => $dataInput,
                    ],
                ],
            ],
            '_savedok' => '1',
        ];
        if ($setControl) {
            $parsedBody['control']['active']['test_persist_null']['1'][$expectedField] = $controlInput;
        }
        $request = $request
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']))
            ->withQueryParams($queryParams)
            ->withParsedBody($parsedBody);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->subject->mainAction($request);

        $record = BackendUtility::getRecord('test_persist_null', 1);
        self::assertSame($expectedValue, $record[$expectedField]);
    }
}
