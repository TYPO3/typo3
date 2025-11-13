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

namespace TYPO3\CMS\Redirects\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Redirects\Controller\ManagementController;
use TYPO3\CMS\Redirects\Event\ModifyRedirectManagementControllerViewDataEvent;
use TYPO3\CMS\Redirects\Repository\Demand;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ManagementControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['redirects'];
    protected ManagementController $subject;
    protected NormalizedParams $normalizedParams;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->subject = $this->get(ManagementController::class);
        $this->normalizedParams = new NormalizedParams([], [], '', '');
    }

    #[Test]
    public function modifyRedirectManagementControllerViewDataEventIsTriggered(): void
    {
        $request = (new ServerRequest('https://www.example.com/', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-redirects']))
            ->withAttribute('moduleData', new ModuleData('redirects', ['redirectType' => Demand::DEFAULT_REDIRECT_TYPE]));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $setHosts = ['*', 'example.com'];

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'modify-redirect-management-controller-view-data-event',
            static function (ModifyRedirectManagementControllerViewDataEvent $event) use (
                &$modifyRedirectManagementControllerViewDataEvent,
                $setHosts
            ): void {
                $modifyRedirectManagementControllerViewDataEvent = $event;
                $event->setHosts($setHosts);
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(ModifyRedirectManagementControllerViewDataEvent::class, 'modify-redirect-management-controller-view-data-event');

        $this->subject->handleRequest($request);

        self::assertInstanceOf(ModifyRedirectManagementControllerViewDataEvent::class, $modifyRedirectManagementControllerViewDataEvent);
        self::assertSame($setHosts, $modifyRedirectManagementControllerViewDataEvent->getHosts());
    }
}
