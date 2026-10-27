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
use TYPO3\CMS\Backend\Controller\EditDocumentController;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Tests\Functional\Controller\Fixtures\RejectNewRecordsHook;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class EditDocumentControllerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/backend/Tests/Functional/Controller/Fixtures/Extensions/test_persist_null',
    ];
    private EditDocumentController $subject;

    private NormalizedParams $normalizedParams;

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/test_persist_null.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->subject = $this->get(EditDocumentController::class);
        $this->normalizedParams = new NormalizedParams([], [], '', '');
    }

    #[Test]
    public function processedDataTakesOverDefaultValues(): void
    {
        $request = new ServerRequest('https://www.example.com/', 'POST')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $defaultValues = [
            'colPos' => 123,
            'CType' => 'bullets',
        ];

        $queryParams = $this->getQueryParamsWithDefaults($defaultValues);
        $parsedBody = $this->getParsedBody();

        $request = $request
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']))
            ->withQueryParams($queryParams)
            ->withParsedBody($parsedBody);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->mainAction($request);

        $newRecord = BackendUtility::getRecord('tt_content', 2);
        self::assertEquals(
            [$defaultValues['colPos'], $defaultValues['CType']],
            [$newRecord['colPos'], $newRecord['CType']]
        );
        // Redirect to GET is applied after processing
        self::assertEquals(302, $response->getStatusCode());
    }

    #[Test]
    public function processedDataDoesNotOverridePostWithDefaultValues(): void
    {
        $request = new ServerRequest('https://www.example.com/', 'POST')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $defaultValues = [
            'colPos' => 123,
            'CType' => 'bullets',
        ];

        $queryParams = $this->getQueryParamsWithDefaults($defaultValues);
        $parsedBody = $this->getParsedBody(['colPos' => 0, 'CType' => 'text']);
        $request = $request
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']))
            ->withQueryParams($queryParams)
            ->withParsedBody($parsedBody);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->mainAction($request);

        $newRecord = BackendUtility::getRecord('tt_content', 2);
        self::assertEquals(
            [0, 'text'],
            [$newRecord['colPos'], $newRecord['CType']],
        );
        // Redirect to GET is applied after processing
        self::assertEquals(302, $response->getStatusCode());
    }

    #[Test]
    public function redirectAfterPersistingNewRecordDropsDefaultValues(): void
    {
        $request = new ServerRequest('https://www.example.com/', 'POST')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']))
            ->withQueryParams($this->getQueryParamsWithDefaults(['CType' => 'bullets']))
            ->withParsedBody($this->getParsedBody());
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->mainAction($request);

        self::assertEquals(302, $response->getStatusCode());
        parse_str((string)parse_url($response->getHeaderLine('Location'), PHP_URL_QUERY), $redirectQueryParams);
        self::assertSame(['tt_content' => ['2' => 'edit']], $redirectQueryParams['edit']);
        self::assertArrayNotHasKey('defVals', $redirectQueryParams);
    }

    #[Test]
    public function redirectAfterRejectedNewRecordKeepsDefaultValues(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['rejectNewRecords'] = RejectNewRecordsHook::class;
        $request = new ServerRequest('https://www.example.com/', 'POST')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']))
            ->withQueryParams($this->getQueryParamsWithDefaults(['CType' => 'bullets']))
            ->withParsedBody($this->getParsedBody());
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->mainAction($request);

        self::assertNull(BackendUtility::getRecord('tt_content', 2));
        self::assertEquals(302, $response->getStatusCode());
        parse_str((string)parse_url($response->getHeaderLine('Location'), PHP_URL_QUERY), $redirectQueryParams);
        self::assertSame(['tt_content' => ['-1' => 'new']], $redirectQueryParams['edit']);
        self::assertSame(['tt_content' => ['CType' => 'bullets']], $redirectQueryParams['defVals'] ?? null);
    }

    #[Test]
    public function redirectAfterSaveAndNewKeepsDefaultValues(): void
    {
        $parsedBody = $this->getParsedBody();
        unset($parsedBody['_savedok']);
        $parsedBody['_savedoknew'] = '1';
        $request = new ServerRequest('https://www.example.com/', 'POST')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']))
            ->withQueryParams($this->getQueryParamsWithDefaults(['CType' => 'bullets']))
            ->withParsedBody($parsedBody);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->mainAction($request);

        self::assertNotNull(BackendUtility::getRecord('tt_content', 2));
        self::assertEquals(302, $response->getStatusCode());
        parse_str((string)parse_url($response->getHeaderLine('Location'), PHP_URL_QUERY), $redirectQueryParams);
        self::assertSame(['tt_content' => ['-2' => 'new']], $redirectQueryParams['edit']);
        self::assertSame(['tt_content' => ['CType' => 'bullets']], $redirectQueryParams['defVals'] ?? null);
    }

    #[Test]
    public function savedokaddrecordRedirectsToWizardAddWithTheRealPersistedUid(): void
    {
        $request = new ServerRequest('https://www.example.com/', 'POST')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $queryParams = [
            'edit' => [
                'tt_content' => [
                    -1 => 'new',
                ],
            ],
        ];
        $parsedBody = [
            'data' => [
                'tt_content' => [
                    'NEW123456' => [
                        'sys_language_uid' => 0,
                        'header' => 'Test header',
                        'pid' => -1,
                    ],
                ],
            ],
            '_savedokaddrecord' => '1',
            'addRecordAfterSave' => [
                'originalUid' => 'NEW123456',
                'ownerTable' => 'tt_content',
                'ownerField' => 'some_group_field',
                'table' => 'sys_category',
                'pid' => '1',
                'setValue' => 'append',
                'flexFormPath' => '',
            ],
        ];

        $request = $request
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']))
            ->withQueryParams($queryParams)
            ->withParsedBody($parsedBody);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $response = $this->subject->mainAction($request);

        self::assertEquals(302, $response->getStatusCode());

        $newRecord = BackendUtility::getRecord('tt_content', 2);
        self::assertSame('Test header', $newRecord['header']);

        $location = $response->getHeaderLine('Location');
        self::assertStringContainsString('wizard/add', $location);
        self::assertStringContainsString('uid%5D=' . $newRecord['uid'], $location);
        self::assertStringContainsString('table%5D=tt_content', $location);
        self::assertStringContainsString('field%5D=some_group_field', $location);
        self::assertStringNotContainsString('NEW123456', $location);

        // Once the wizard is done, it must redirect back to the document that was
        // being edited (built server-side from the now-persisted editconf), not to
        // whatever URL a client happened to submit. See add-record.ts, which
        // deliberately no longer sends its own returnUrl for this exact reason.
        $decodedLocation = rawurldecode(rawurldecode($location));
        self::assertStringContainsString('typo3/record/edit', $decodedLocation);
        self::assertStringContainsString('edit[tt_content][' . $newRecord['uid'] . ']=edit', $decodedLocation);
    }

    #[Test]
    public function savedokaddrecordKeepsQueryParametersOfTheCurrentDocumentInTheReturnUrl(): void
    {
        $request = new ServerRequest('https://www.example.com/', 'POST')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        // This is how Wizard/AddController opens a new record: "returnEditConf" tells it
        // to link the record back into the field of the document that started the wizard,
        // once this document is closed. It must survive a nested "+" click, otherwise the
        // outer wizard never links anything.
        $queryParams = [
            'edit' => [
                'tt_content' => [
                    -1 => 'new',
                ],
            ],
            'returnEditConf' => 1,
            'columnsOnly' => [
                'tt_content' => ['header'],
            ],
        ];
        $parsedBody = [
            'data' => [
                'tt_content' => [
                    'NEW123456' => [
                        'sys_language_uid' => 0,
                        'header' => 'Test header',
                        'pid' => -1,
                    ],
                ],
            ],
            '_savedok' => '1',
            '_savedokaddrecord' => '1',
            'addRecordAfterSave' => [
                'originalUid' => 'NEW123456',
                'ownerTable' => 'tt_content',
                'ownerField' => 'some_group_field',
                'table' => 'sys_category',
                'pid' => '1',
                'setValue' => 'append',
                'flexFormPath' => '',
            ],
        ];

        $request = $request
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']))
            ->withQueryParams($queryParams)
            ->withParsedBody($parsedBody);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $response = $this->subject->mainAction($request);

        self::assertEquals(302, $response->getStatusCode());

        $decodedLocation = rawurldecode(rawurldecode($response->getHeaderLine('Location')));
        self::assertStringContainsString('returnEditConf=1', $decodedLocation);
        self::assertStringContainsString('columnsOnly[tt_content][0]=header', $decodedLocation);
    }

    #[Test]
    public function documentTitleIsEscapedOnce(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tt_content')
            ->update('tt_content', ['header' => 'Sample text & media'], ['uid' => 1]);

        $request = new ServerRequest('https://www.example.com/typo3/record/edit', 'GET')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']))
            ->withQueryParams(['edit' => ['tt_content' => [1 => 'edit']]]);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $html = (string)$this->subject->mainAction($request)->getBody();

        self::assertStringContainsString('<title>Sample text &amp; media · ', $html);
        self::assertStringContainsString('display-name="Sample text &amp; media · ', $html);
    }

    private function getParsedBody(array $additionalData = []): array
    {
        return [
            'data' => [
                'tt_content' => [
                    'NEW123456' => array_replace_recursive([
                        'sys_language_uid' => 0,
                        'header' => 'Test header',
                        'pid' => -1,
                    ], $additionalData),
                ],
            ],
            '_savedok' => '1',
        ];
    }

    private function getQueryParamsWithDefaults(array $defaultValues): array
    {
        return [
            'edit' => [
                'tt_content' => [
                    -1 => 'new',
                ],
            ],
            'defVals' => [
                'tt_content' => $defaultValues,
            ],
        ];
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
    public function saveNullValuesViaControlForNewRecords(string $expectedField, mixed $dataInput, bool $setControl, int $controlInput, mixed $expectedValue): void
    {
        $request = new ServerRequest('https://www.example.com/', 'POST')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $queryParams = [
            'edit' => [
                'test_persist_null' => [
                    '0' => 'new',
                ],
            ],
        ];
        $parsedBody = [
            'data' => [
                'test_persist_null' => [
                    'NEW123456' => [
                        'pid' => 0,
                        $expectedField => $dataInput,
                    ],
                ],
            ],
            '_savedok' => '1',
        ];
        if ($setControl) {
            $parsedBody['control']['active']['test_persist_null']['NEW123456'][$expectedField] = $controlInput;
        }
        $request = $request
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']))
            ->withQueryParams($queryParams)
            ->withParsedBody($parsedBody);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->mainAction($request);
        $decodedLocation = rawurldecode(rawurldecode($response->getHeaderLine('Location')));
        $query = parse_url($decodedLocation, PHP_URL_QUERY);
        parse_str($query, $queryParams);
        $uid = array_key_first($queryParams['edit']['test_persist_null']);

        $newRecord = BackendUtility::getRecord('test_persist_null', $uid);
        self::assertSame($expectedValue, $newRecord[$expectedField]);
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
