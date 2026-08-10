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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller\Wizard;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\Wizard\PageWizardController;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageWizardControllerTest extends FunctionalTestCase
{
    private PageWizardController $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_with_editor.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->subject = $this->get(PageWizardController::class);

    }

    #[Test]
    public function allDoktypesAreReturnedFromFormDataCompiler(): void
    {
        $request = new ServerRequest('https://example.com/typo3/', 'GET')->withQueryParams([
            'data' => [
                'position' => [
                    'pageUid' => 1,
                    'insertPosition' => 'inside',
                ],
            ],
        ]);

        $response = $this->subject->getDoktypesAction($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(
            [
                [
                    'value' => '--div--',
                    'label' => 'Page',
                    'icon' => '',
                    'description' => '',
                ],
                [
                    'value' => '1',
                    'label' => 'Standard Page',
                    'icon' => 'apps-pagetree-page-default',
                    'description' => '',
                ],
                [
                    'value' => '6',
                    'label' => 'Backend User Section',
                    'icon' => 'apps-pagetree-page-backend-users',
                    'description' => '',
                ],
                [
                    'value' => '--div--',
                    'label' => 'Link',
                    'icon' => '',
                    'description' => '',
                ],
                [
                    'value' => '4',
                    'label' => 'Shortcut',
                    'icon' => 'apps-pagetree-page-shortcut',
                    'description' => '',
                ],
                [
                    'value' => '7',
                    'label' => 'Mount Point',
                    'icon' => 'apps-pagetree-page-mountpoint',
                    'description' => '',
                ],
                [
                    'value' => '3',
                    'label' => 'Link',
                    'icon' => 'apps-pagetree-page-shortcut-external',
                    'description' => '',
                ],
                [
                    'value' => '--div--',
                    'label' => 'Special',
                    'icon' => '',
                    'description' => '',
                ],
                [
                    'value' => '254',
                    'label' => 'Folder',
                    'icon' => 'apps-pagetree-folder-default',
                    'description' => '',
                ],
                [
                    'value' => '199',
                    'label' => 'Menu Separator',
                    'icon' => 'apps-pagetree-spacer',
                    'description' => '',
                ],
            ],
            $payload
        );
    }

    #[Test]
    public function getPageDetailActionReturns400IfPageUidIsMissing(): void
    {
        $request = new ServerRequest('https://example.com/typo3/', 'GET')->withQueryParams([]);

        $response = $this->subject->getPageDetailAction($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(400, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(
            ['error' => 'Missing required query parameter: pageUid'],
            $body
        );
    }

    #[Test]
    public function getPageDetailActionReturns403IfPageDoesNotExist(): void
    {
        $request = new ServerRequest('https://example.com/typo3/', 'GET')->withQueryParams([
            'pageUid' => '999',
        ]);

        $response = $this->subject->getPageDetailAction($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(403, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);
        self::assertNull($body);
    }

    #[Test]
    public function getPageDetailActionReturnsPageDetailsIfPageExists(): void
    {
        $request = self::createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'pageUid' => '1',
        ]);

        $response = $this->subject->getPageDetailAction($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);

        self::assertSame([
            'uid' => 1,
            'title' => 'Root',
            'icon' => 'apps-pagetree-page-default',
        ], $body);
    }

    #[Test]
    public function getPageDetailActionReturnsStaticRootOnPageUidZero(): void
    {
        $request = self::createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'pageUid' => '0',
        ]);

        $response = $this->subject->getPageDetailAction($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);

        self::assertSame([
            'uid' => 0,
            'title' => 'New TYPO3 site',
            'icon' => 'apps-pagetree-root',
        ], $body);
    }
    #[Test]
    public function getDoktypesActionReturns403IfMissingNewPagePermission(): void
    {
        // Use a non-admin user
        $this->setUpBackendUser(9);
        // Permission 1 is PAGE_SHOW only, so no PAGE_NEW (8)
        $this->updatePagePermissions(1, 1);

        $request = new ServerRequest('https://example.com/typo3/', 'GET')->withQueryParams([
            'data' => [
                'position' => [
                    'pageUid' => 1,
                    'insertPosition' => 'inside',
                ],
            ],
        ]);

        $response = $this->subject->getDoktypesAction($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function getPageDetailActionReturns403IfMissingShowPermission(): void
    {
        // Use a non-admin user
        $this->setUpBackendUser(9);
        // Permission 0 is NOTHING
        $this->updatePagePermissions(1, 0);

        $request = new ServerRequest('https://example.com/typo3/', 'GET')->withQueryParams([
            'pageUid' => '1',
        ]);

        $response = $this->subject->getPageDetailAction($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function getProcessedValueActionReturns403IfMissingShowPermission(): void
    {
        // Use a non-admin user
        $this->setUpBackendUser(9);
        // Permission 0 is NOTHING
        $this->updatePagePermissions(1, 0);

        $request = new ServerRequest('https://example.com/typo3/', 'GET')->withQueryParams([
            'pageUid' => '1',
            'fields' => ['title' => 'foo'],
        ]);

        $response = $this->subject->getProcessedValueAction($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(403, $response->getStatusCode());
    }

    private function updatePagePermissions(int $uid, int $permissions): void
    {
        $this->getConnectionPool()->getConnectionForTable('pages')->update(
            'pages',
            ['perms_everybody' => $permissions],
            ['uid' => $uid]
        );
    }
}
