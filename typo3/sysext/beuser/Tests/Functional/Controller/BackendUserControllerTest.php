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

namespace TYPO3\CMS\Beuser\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Beuser\Controller\BackendUserController;
use TYPO3\CMS\Beuser\Domain\Repository\FileMountRepository;
use TYPO3\CMS\Beuser\Event\AfterBackendGroupFilterListIsAssembledEvent;
use TYPO3\CMS\Beuser\Event\AfterFilemountsListIsAssembledEvent;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BackendUserControllerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'beuser',
        // Needed for the filemounts action test
        'filelist',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function listActionDispatchesProcessBackendGroupFilterListEvent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_groups.csv');

        $dispatchedEvents = [];
        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            'after-backend-group-filter-list-is-assembled-event-is-dispatched',
            static function (AfterBackendGroupFilterListIsAssembledEvent $event) use (&$dispatchedEvents) {
                // Simulate filtering the list by just removing the last item from it
                array_pop($event->backendGroups);
                $dispatchedEvents[] = $event;
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(
            AfterBackendGroupFilterListIsAssembledEvent::class,
            'after-backend-group-filter-list-is-assembled-event-is-dispatched'
        );
        $request = $this->assembleRequest('list');
        $subject = $this->get(BackendUserController::class);
        $subject->processRequest(new Request($request));
        self::assertCount(1, $dispatchedEvents);
        // NOTE: the backend groups list comes with an empty item at the start, to allow for not filtering the users list,
        // so it's one more item than in the database
        self::assertCount(2, $dispatchedEvents[0]->backendGroups);
    }

    #[Test]
    public function filemountsActionDispatchesProcessFilemountsListEvent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_filemounts.csv');
        $filemountRepository = $this->get(FileMountRepository::class);
        $filemounts = $filemountRepository->findBy(['title' => 'Default'])->toArray();

        $dispatchedEvents = [];
        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            'after-filemounts-list-is-assembled-event-is-dispatched',
            static function (AfterFilemountsListIsAssembledEvent $event) use (&$dispatchedEvents, $filemounts) {
                // Default is a list of all filemounts, override with custom selection for testing
                $event->filemounts = $filemounts;
                $dispatchedEvents[] = $event;
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(
            AfterFilemountsListIsAssembledEvent::class,
            'after-filemounts-list-is-assembled-event-is-dispatched'
        );
        $request = $this->assembleRequest('filemounts');
        $subject = $this->get(BackendUserController::class);
        $subject->processRequest(new Request($request));
        self::assertCount(1, $dispatchedEvents);
        self::assertEquals('Default', $dispatchedEvents[0]->filemounts[0]->getTitle());
    }

    protected function assembleRequest(string $action): ServerRequest
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setPluginName('backend_user_management');
        $extbaseRequestParameters->setControllerActionName($action);
        $extbaseRequestParameters->setControllerName('BackendUser');
        $extbaseRequestParameters->setControllerExtensionName('Beuser');
        $request = (new ServerRequest('https://example.com/typo3/main'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('moduleData', new ModuleData('backend_user_management', []))
            ->withAttribute('normalizedParams', new NormalizedParams([], [], '', ''))
            ->withAttribute(
                'route',
                new Route(
                    '/module/system/user-management/BackendUser/list',
                    [
                        'packageName' => 'typo3/cms-beuser',
                        '_identifier' => 'backend_user_management',
                    ]
                )
            );
        // Needed because ConfigurationManager is injected into the Controller (DI) but needs a request
        // Thank you, Extbase!
        $GLOBALS['TYPO3_REQUEST'] = $request;
        return $request;
    }
}
