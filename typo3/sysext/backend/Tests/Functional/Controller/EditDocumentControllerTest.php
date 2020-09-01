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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Controller\EditDocumentController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for TYPO3\CMS\Backend\Controller\EditDocumentController
 */
class EditDocumentControllerTest extends FunctionalTestCase
{
    /**
     * @var EditDocumentController
     */
    protected $subject;

    /**
     * @var ServerRequest
     */
    protected $request;

    /**
     * @var NormalizedParams
     */
    protected $normalizedParams;

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/tt_content.xml');

        $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->subject = new EditDocumentController($eventDispatcher->reveal());
        $this->request = new ServerRequest();
        $this->normalizedParams = new NormalizedParams([], [], '', '');
    }

    /**
     * @test
     */
    public function processedDataTakesOverDefaultValues(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $defaultValues = [
            'colPos' => 123,
            'CType' => 'bullets'
        ];

        $queryParams = $this->getQueryParamsWithDefaults($defaultValues);
        $parsedBody = $this->getParsedBody();

        $this->subject->mainAction(
            $this->request
                ->withAttribute('normalizedParams', $this->normalizedParams)
                ->withQueryParams($queryParams)
                ->withParsedBody($parsedBody)
        );

        $newRecord = BackendUtility::getRecord('tt_content', 2);
        self::assertEquals(
            [$newRecord['colPos'], $newRecord['CType']],
            [$defaultValues['colPos'], $defaultValues['CType']]
        );
    }

    /**
     * @test
     */
    public function processedDataDoesNotOverridePostWithDefaultValues(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $defaultValues = [
            'colPos' => 123,
            'CType' => 'bullets'
        ];

        $queryParams = $this->getQueryParamsWithDefaults($defaultValues);
        $parsedBody = $this->getParsedBody(['colPos' => 0, 'CType' => 'text']);

        $this->subject->mainAction(
            $this->request
                ->withAttribute('normalizedParams', $this->normalizedParams)
                ->withQueryParams($queryParams)
                ->withParsedBody($parsedBody)
        );

        $newRecord = BackendUtility::getRecord('tt_content', 2);
        self::assertEquals(
            [$newRecord['colPos'], $newRecord['CType']],
            [0, 'text']
        );
    }

    protected function getParsedBody(array $additionalData = []): array
    {
        return [
            'data' => [
              'tt_content' => [
                  'NEW123456' => \array_replace_recursive([
                      'sys_language_uid' => 0,
                      'header' => 'Test header',
                      'pid' => -1,
                  ], $additionalData)
              ]
            ],
            'doSave' => true
        ];
    }

    protected function getQueryParamsWithDefaults(array $defaultValues): array
    {
        return [
            'edit' => [
                'tt_content' => [
                    -1 => 'new'
                ]
            ],
            'defVals' => [
                'tt_content' => $defaultValues
            ]
        ];
    }
}
