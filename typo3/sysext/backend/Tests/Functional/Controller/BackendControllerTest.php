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
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Controller\BackendController;
use TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BackendControllerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'workspaces',
        'typo3/sysext/backend/Tests/Functional/Fixtures/Extensions/test_backend_logo',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function backendPageRenderEventIsTriggered(): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');

        $state = [
            'after-backend-page-render-listener' => null,
        ];

        // Dummy listeners that just record that the event existed.
        $container->set(
            'after-backend-page-render-listener',
            static function (AfterBackendPageRenderEvent $event) use (&$state) {
                $state['after-backend-page-render-listener'] = $event;
            }
        );

        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(AfterBackendPageRenderEvent::class, 'after-backend-page-render-listener');

        $request = (new ServerRequest('https://example.com/typo3/main'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/main', ['packageName' => 'typo3/cms-backend', '_identifier' => 'main']));

        $request = $request
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $GLOBALS['TYPO3_REQUEST'] = $request;
        $subject = $this->get(BackendController::class);
        $subject->mainAction($request);

        self::assertInstanceOf(AfterBackendPageRenderEvent::class, $state['after-backend-page-render-listener']);
    }

    public static function backendLogoIsRenderedFromConfiguredResourceDataProvider(): array
    {
        return [
            'extension path to svg' => [
                'EXT:core/Resources/Public/Images/typo3_variable.svg',
                'Images/typo3_variable.svg',
                150,
                52,
            ],
            'package resource identifier to svg' => [
                'PKG:typo3/cms-core:Resources/Public/Images/typo3_variable.svg',
                'Images/typo3_variable.svg',
                150,
                52,
            ],
            'extension path to raster image' => [
                'EXT:backend/Resources/Public/Images/default.gif',
                'Images/default.gif',
                18,
                16,
            ],
            'extension path to high resolution raster image' => [
                'EXT:test_backend_logo/Resources/Public/Images/logo@2x.png',
                'Images/logo@2x.png',
                22,
                11,
            ],
            'package resource identifier to high resolution raster image' => [
                'PKG:typo3tests/cms-test-backend-logo:Resources/Public/Images/logo@2x.png',
                'Images/logo@2x.png',
                22,
                11,
            ],
            'leading slash is stripped from the configured identifier' => [
                '/EXT:core/Resources/Public/Images/typo3_variable.svg',
                'Images/typo3_variable.svg',
                150,
                52,
            ],
        ];
    }

    #[DataProvider('backendLogoIsRenderedFromConfiguredResourceDataProvider')]
    #[Test]
    public function backendLogoIsRenderedFromConfiguredResource(string $configuredLogo, string $expectedUriPart, int $expectedWidth, int $expectedHeight): void
    {
        $logo = $this->renderTopbarLogo($configuredLogo);

        self::assertStringContainsString($expectedUriPart, $logo);
        self::assertStringContainsString('width="' . $expectedWidth . '"', $logo);
        self::assertStringContainsString('height="' . $expectedHeight . '"', $logo);
    }

    public static function backendLogoFallsBackToDefaultLogoDataProvider(): array
    {
        return [
            'not configured' => [''],
            'extension path to missing file' => ['EXT:backend/Resources/Public/Images/does-not-exist.svg'],
            'package resource identifier to missing file' => ['PKG:typo3/cms-backend:Resources/Public/Images/does-not-exist.svg'],
            'unknown package' => ['PKG:vendor/does-not-exist:Resources/Public/Images/logo.svg'],
            'external url' => ['https://example.com/logo.svg'],
            'no resource identifier at all' => ['this is not a resource identifier'],
        ];
    }

    #[DataProvider('backendLogoFallsBackToDefaultLogoDataProvider')]
    #[Test]
    public function backendLogoFallsBackToDefaultLogo(string $configuredLogo): void
    {
        $logo = $this->renderTopbarLogo($configuredLogo);

        self::assertStringContainsString('Images/typo3_logo_orange.svg', $logo);
        self::assertStringContainsString('width="22"', $logo);
        self::assertStringContainsString('height="22"', $logo);
    }

    /**
     * Renders the backend with the given "backendLogo" extension configuration
     * and returns the topbar logo image tag.
     */
    private function renderTopbarLogo(string $configuredLogo): string
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['backend']['backendLogo'] = $configuredLogo;
        $request = (new ServerRequest('https://example.com/typo3/main'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/main', ['packageName' => 'typo3/cms-backend', '_identifier' => 'main']));
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $body = (string)$this->get(BackendController::class)->mainAction($request)->getBody();
        self::assertSame(1, preg_match('#<span class="topbar-site-logo">\s*(<img[^>]*>)#', $body, $matches));

        return $matches[1];
    }

    #[Test]
    public function flashMessageIsDispatchedForForcedRedirect(): void
    {
        // Set workspace to disable the site configuration module
        $GLOBALS['BE_USER']->workspace = 1;

        $request = (new ServerRequest('https://example.com/typo3/main'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams(['redirect' => 'site_configuration'])
            ->withAttribute('route', new Route('/main', ['packageName' => 'typo3/cms-backend', '_identifier' => 'main']));

        $request = $request
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->get(BackendController::class)->mainAction($request);

        $flashMessage = $this->get(FlashMessageService::class)
            ->getMessageQueueByIdentifier(FlashMessageQueue::NOTIFICATION_QUEUE)
            ->getAllMessages()[0] ?? null;

        self::assertInstanceOf(FlashMessage::class, $flashMessage);
        self::assertEquals('No module access', $flashMessage->getTitle());
        self::assertEquals(ContextualFeedbackSeverity::INFO, $flashMessage->getSeverity());
    }
}
