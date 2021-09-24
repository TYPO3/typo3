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

use TYPO3\CMS\Backend\Controller\ShortcutController;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ShortcutControllerTest extends FunctionalTestCase
{
    protected ShortcutController $subject;
    protected ServerRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../Fixtures/sys_be_shortcuts.xml');

        $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $this->subject = new ShortcutController();
        $this->request = (new ServerRequest())->withAttribute('normalizedParams', new NormalizedParams([], [], '', ''));
    }

    /**
     * @dataProvider addShortcutTestDataProvide
     * @test
     *
     * @param array $parsedBody
     * @param array $queryParams
     * @param string $expectedResponseBody
     */
    public function addShortcutTest(array $parsedBody, array $queryParams, string $expectedResponseBody): void
    {
        $request = $this->request->withParsedBody($parsedBody)->withQueryParams($queryParams);
        self::assertEquals($expectedResponseBody, $this->subject->addAction($request)->getBody());
    }

    public function addShortcutTestDataProvide(): \Generator
    {
        yield 'No route defined' => [
            [],
            [],
            'missingRoute',
        ];
        yield 'Existing data as parsed body' => [
            [
                'routeIdentifier' => 'web_layout',
                'arguments' => '{"id":"123"}',
            ],
            [],
            'alreadyExists',
        ];
        yield 'Existing data as query parameters' => [
            [],
            [
                'routeIdentifier' => 'web_layout',
                'arguments' => '{"id":"123"}',
            ],
            'alreadyExists',
        ];
        yield 'Invalid route identifier' => [
            [],
            [
                'routeIdentifier' => 'invalid_route_identifier',
            ],
            'failed',
        ];
        yield 'New data as parsed body' => [
            [
                'routeIdentifier' => 'web_list',
                'arguments' => '{"id":"123","GET":{"clipBoard":"1"}}',
            ],
            [],
            'success',
        ];
        yield 'New data as query parameters' => [
            [],
            [
                'routeIdentifier' => 'web_list',
                'arguments' => '{"id":"321","GET":{"clipBoard":"1"}}',
            ],
            'success',
        ];
    }
}
