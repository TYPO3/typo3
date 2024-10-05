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
    protected array $testExtensionsToLoad = ['workspaces'];

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
