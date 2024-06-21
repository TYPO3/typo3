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

namespace TYPO3\CMS\RteCKEditor\Tests\Functional\RecordList\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Controller\Event\ModifyAllowedItemsEvent;
use TYPO3\CMS\Backend\Controller\Event\ModifyLinkHandlersEvent;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\RteCKEditor\Controller\BrowseLinksController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BrowseLinksControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'rte_ckeditor',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function linkEventsAreTriggered(): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');

        $state = [
            'modify-link-handl-listener' => null,
            'after-backend-page-render-listener' => null,
        ];

        // Dummy listeners that just record that the event existed.
        $container->set(
            'modify-link-handler-listener',
            static function (ModifyLinkHandlersEvent $event) use (&$state) {
                $state['modify-link-handler-listener'] = $event;
            }
        );
        $container->set(
            'modify-allowed-items-listener',
            static function (ModifyAllowedItemsEvent $event) use (&$state) {
                $state['modify-allowed-items-listener'] = $event;
            }
        );

        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(ModifyLinkHandlersEvent::class, 'modify-link-handler-listener');
        $eventListener->addListener(ModifyAllowedItemsEvent::class, 'modify-allowed-items-listener');

        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/main', [
                'packageName' => 'typo3/cms-rte-ckeditor',
                '_identifier' => 'main',
            ]))
            ->withQueryParams([
                'editorId' => 'cke_1',
                'contentsLanguage' => 'en',
                'P' => [
                    'table' => 'tt_content',
                    'uid' => '1',
                    'fieldName' => 'bodytext',
                    'recordType' => 'text',
                    'pid' => '1',
                    'richtextConfigurationName' => '',
                ],
            ]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $subject = $this->get(BrowseLinksController::class);

        $subject->mainAction($request);

        self::assertInstanceOf(ModifyLinkHandlersEvent::class, $state['modify-link-handler-listener']);
        self::assertInstanceOf(ModifyAllowedItemsEvent::class, $state['modify-allowed-items-listener']);
    }
}
