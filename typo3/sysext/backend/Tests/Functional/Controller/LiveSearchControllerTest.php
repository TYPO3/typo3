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
use TYPO3\CMS\Backend\Controller\LiveSearchController;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Search\Event\BeforeLiveSearchFormIsBuiltEvent;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LiveSearchControllerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        __DIR__ . '/../Fixtures/Extensions/test_live_search_controller_events',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function formActionDispatchesBeforeLiveSearchFormIsBuiltEvent(): void
    {
        $dispatchedEvents = [];
        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            'modify-live-search-form-data-event-is-dispatched',
            static function (BeforeLiveSearchFormIsBuiltEvent $event) use (&$dispatchedEvents) {
                $dispatchedEvents[] = $event;
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(BeforeLiveSearchFormIsBuiltEvent::class, 'modify-live-search-form-data-event-is-dispatched');

        $request = (new ServerRequest('https://example.com/typo3/main'));
        $request = $request
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/livesearch/form', ['packageName' => 'typo3/cms-backend', '_identifier' => 'livesearch_form']));

        $GLOBALS['TYPO3_REQUEST'] = $request;
        $subject = $this->get(LiveSearchController::class);
        $subject->formAction($request);

        self::assertCount(1, $dispatchedEvents);
    }

    #[Test]
    public function formActionBeforeLiveSearchFormIsBuiltEventContainsDemandOnPlainRequest(): void
    {
        $dispatchedEvents = [
            'dispatches' => [],
            'last-modify-live-search-form-data-event' => null,
        ];
        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            'modify-live-search-form-data-event-is-dispatched',
            static function (BeforeLiveSearchFormIsBuiltEvent $event) use (&$dispatchedEvents) {
                $dispatchedEvents['dispatches'][] = $event;
                $dispatchedEvents['last-modify-live-search-form-data-event'] = $event;
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(BeforeLiveSearchFormIsBuiltEvent::class, 'modify-live-search-form-data-event-is-dispatched');

        $request = (new ServerRequest('https://example.com/typo3/main'));
        $request = $request
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/livesearch/form', ['packageName' => 'typo3/cms-backend', '_identifier' => 'livesearch_form']));

        $GLOBALS['TYPO3_REQUEST'] = $request;
        $subject = $this->get(LiveSearchController::class);
        $subject->formAction($request);

        $lastEventObject = $dispatchedEvents['last-modify-live-search-form-data-event'] ?? null;

        self::assertCount(1, $dispatchedEvents['dispatches']);
        self::assertInstanceOf(BeforeLiveSearchFormIsBuiltEvent::class, $lastEventObject);
    }

    #[Test]
    public function formActionRendersAddedHintsUsingBeforeLiveSearchFormIsBuiltEvent(): void
    {
        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            'modify-live-search-form-data-event-is-dispatched',
            static function (BeforeLiveSearchFormIsBuiltEvent $event) {
                $event->setHints(['LLL:EXT:test_live_search_controller_events/Resources/Private/Language/locallang_misc.xlf:livesearch.dummy-live-search-hint-label']);
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(BeforeLiveSearchFormIsBuiltEvent::class, 'modify-live-search-form-data-event-is-dispatched');

        $request = (new ServerRequest('https://example.com/typo3/main'));
        $request = $request
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/livesearch/form', ['packageName' => 'typo3/cms-backend', '_identifier' => 'livesearch_form']));

        $GLOBALS['TYPO3_REQUEST'] = $request;
        $subject = $this->get(LiveSearchController::class);
        $response = $subject->formAction($request);
        $responseBody = (string)$response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Custom &quot;LiveSearch&quot; Form Hint', $responseBody);
    }

    #[Test]
    public function formActionResponseBodyContainsDefaultProviderInDropDown(): void
    {
        /** @var BeforeLiveSearchFormIsBuiltEvent|null $dispatchedEvent */
        $dispatchedEvent = null;
        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            'modify-live-search-form-data-event-is-dispatched',
            static function (BeforeLiveSearchFormIsBuiltEvent $event) use (&$dispatchedEvent) {
                $dispatchedEvent = $event;
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(BeforeLiveSearchFormIsBuiltEvent::class, 'modify-live-search-form-data-event-is-dispatched');

        $request = (new ServerRequest('https://example.com/typo3/main'));
        $request = $request
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/livesearch/form', ['packageName' => 'typo3/cms-backend', '_identifier' => 'livesearch_form']));

        $GLOBALS['TYPO3_REQUEST'] = $request;
        $subject = $this->get(LiveSearchController::class);
        $response = $subject->formAction($request);
        $responseBody = (string)$response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertInstanceOf(BeforeLiveSearchFormIsBuiltEvent::class, $dispatchedEvent);

        self::assertStringContainsString('optionId="TYPO3\CMS\Backend\Search\LiveSearch\PageRecordProvider"', $responseBody);
        self::assertStringContainsString('optionId="TYPO3\CMS\Backend\Search\LiveSearch\DatabaseRecordProvider"', $responseBody);
    }
}
