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

use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Controller\BackendController;
use TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendControllerTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        Bootstrap::initializeLanguageObject();
    }

    /**
     * @test
     */
    public function backendPageRenderEventIsTriggered(): void
    {
        /** @var Container $container */
        $container = $this->getContainer();

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

        $eventListener = GeneralUtility::makeInstance(ListenerProvider::class);
        $eventListener->addListener(AfterBackendPageRenderEvent::class, 'after-backend-page-render-listener');

        $request = (new ServerRequest('https://example.com/typo3/main'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/main', ['packageName' => 'typo3/cms-backend', '_identifier' => 'main']));

        $GLOBALS['TYPO3_REQUEST'] = $request;
        $subject = $this->get(BackendController::class);
        $subject->mainAction($request);

        self::assertInstanceOf(AfterBackendPageRenderEvent::class, $state['after-backend-page-render-listener']);
    }
}
