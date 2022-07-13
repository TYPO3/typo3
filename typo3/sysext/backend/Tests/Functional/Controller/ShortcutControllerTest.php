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

use TYPO3\CMS\Backend\Backend\Shortcut\ShortcutRepository;
use TYPO3\CMS\Backend\Backend\ToolbarItems\ShortcutToolbarItem;
use TYPO3\CMS\Backend\Controller\ShortcutController;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ShortcutControllerTest extends FunctionalTestCase
{
    protected ShortcutController $subject;
    protected ServerRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_be_shortcuts.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        Bootstrap::initializeLanguageObject();

        $this->subject = new ShortcutController(
            $this->get(ShortcutToolbarItem::class),
            $this->get(ShortcutRepository::class),
            new BackendViewFactory($this->get(RenderingContextFactory::class), $this->get(PackageManager::class))
        );
        $this->request = (new ServerRequest())->withAttribute('normalizedParams', new NormalizedParams([], [], '', ''));
    }

    /**
     * @dataProvider addShortcutTestDataProvide
     * @test
     */
    public function addShortcutTest(array $parsedBody, string $expectedResponseBody): void
    {
        $request = $this->request->withParsedBody($parsedBody);
        self::assertEquals($expectedResponseBody, $this->subject->addAction($request)->getBody());
    }

    public function addShortcutTestDataProvide(): \Generator
    {
        yield 'No route defined' => [
            [],
            'missingRoute',
        ];
        yield 'Existing data as parsed body' => [
            [
                'routeIdentifier' => 'web_layout',
                'arguments' => '{"id":"123"}',
            ],
            'alreadyExists',
        ];
        yield 'Invalid route identifier' => [
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
            'success',
        ];
    }
}
