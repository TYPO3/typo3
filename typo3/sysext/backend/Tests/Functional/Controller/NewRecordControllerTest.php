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

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Controller\Event\ModifyNewRecordCreationLinksEvent;
use TYPO3\CMS\Backend\Controller\NewRecordController;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class NewRecordControllerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function modifyNewRecordCreationLinksEventIsDispatched(): void
    {
        $eventReference = null;
        $capturedGroupedLinks = null;
        $capturedPageId = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'test-new-record-event-listener',
            static function (ModifyNewRecordCreationLinksEvent $event) use (&$eventReference, &$capturedGroupedLinks, &$capturedPageId) {
                $eventReference = $event;
                $capturedGroupedLinks = $event->groupedCreationLinks;
                $capturedPageId = $event->pageId;

                // Modify the links to test event functionality
                $event->groupedCreationLinks['test_group'] = [
                    'title' => 'Test Group',
                    'icon' => '<span>test-icon</span>',
                    'items' => [
                        'test_table' => [
                            'url' => '/test/url',
                            'icon' => '<span>test-table-icon</span>',
                            'label' => 'Test Table',
                        ],
                    ],
                ];
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(ModifyNewRecordCreationLinksEvent::class, 'test-new-record-event-listener');

        $request = $this->createRequest('/record/new', ['id' => 1]);
        $controller = $this->get(NewRecordController::class);

        $response = $controller->mainAction($request);
        $content = (string)$response->getBody();

        // Verify event was dispatched
        self::assertInstanceOf(ModifyNewRecordCreationLinksEvent::class, $eventReference);
        self::assertArrayNotHasKey('test_group', $capturedGroupedLinks);
        self::assertArrayHasKey('test_group', $eventReference->groupedCreationLinks);
        self::assertEquals(1, $capturedPageId);

        // Verify event modification worked
        self::assertStringContainsString('Test Group', $content);
        self::assertStringContainsString('Test Table', $content);
    }

    #[Test]
    #[IgnoreDeprecations] // This is triggered by GeneralUtility::sanitizeLocalUrl() on resolving the returnUrl
    public function eventReceivesCorrectRequest(): void
    {
        $capturedRequest = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'test-request-capture-listener',
            static function (ModifyNewRecordCreationLinksEvent $event) use (&$capturedRequest) {
                $capturedRequest = $event->request;
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(ModifyNewRecordCreationLinksEvent::class, 'test-request-capture-listener');

        $request = $this->createRequest('/record/new', ['id' => 1, 'returnUrl' => 'test-return']);
        $controller = $this->get(NewRecordController::class);

        $controller->mainAction($request);

        // Verify request object is passed correctly
        self::assertInstanceOf(ServerRequest::class, $capturedRequest);
        self::assertEquals(1, $capturedRequest->getQueryParams()['id']);
        self::assertEquals('test-return', $capturedRequest->getQueryParams()['returnUrl']);
    }

    #[Test]
    public function controllerHandlesRecordTypesWithSubSchemas(): void
    {
        $request = $this->createRequest('/record/new', ['id' => 1]);
        $controller = $this->get(NewRecordController::class);

        $response = $controller->mainAction($request);
        $content = (string)$response->getBody();

        self::assertEquals(200, $response->getStatusCode());
        self::assertStringContainsString('list-group mt-2', $content);
        self::assertStringContainsString('Folder from Storage', $content);
    }

    #[Test]
    public function controllerRespectsTSConfigDeniedTables(): void
    {
        // Set up page TSconfig to deny specific tables
        $connection = $this->getConnectionPool()->getConnectionForTable('pages');
        $connection->update(
            'pages',
            ['TSconfig' => 'mod.web_list.deniedNewTables = sys_file_collection'],
            ['uid' => 1]
        );

        $request = $this->createRequest('/record/new', ['id' => 1]);
        $controller = $this->get(NewRecordController::class);

        $response = $controller->mainAction($request);
        $content = (string)$response->getBody();

        self::assertEquals(200, $response->getStatusCode());
        self::assertStringNotContainsString('Folder from Storage', $content);
    }

    private function createRequest(string $path, array $queryParams = []): ServerRequest
    {
        $normalizedParams = new NormalizedParams([], [], '', '');

        return (new ServerRequest('http://localhost' . $path, 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('route', new Route($path, ['packageName' => 'typo3/cms-backend']))
            ->withQueryParams($queryParams);
    }
}
