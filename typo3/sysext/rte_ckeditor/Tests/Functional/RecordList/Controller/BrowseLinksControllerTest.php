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

use PHPUnit\Framework\Attributes\DataProvider;
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

        $subject = $this->get(BrowseLinksController::class);

        $subject->mainAction($this->createLinkBrowserRequest());

        self::assertInstanceOf(ModifyLinkHandlersEvent::class, $state['modify-link-handler-listener']);
        self::assertInstanceOf(ModifyAllowedItemsEvent::class, $state['modify-allowed-items-listener']);
    }

    public static function defaultTargetFixturesDataProvider(): \Generator
    {
        yield 'RTE configuration buttons.link.properties.target.default' => [
            __DIR__ . '/../../Fixtures/rte-pages-link-target-default.csv',
        ];
        yield 'page TSconfig TCEMAIN.linkHandler.properties.target.default' => [
            __DIR__ . '/../../Fixtures/rte-pages-link-handler-target-default.csv',
        ];
    }

    /**
     * A new link gets the configured default target.
     */
    #[DataProvider('defaultTargetFixturesDataProvider')]
    #[Test]
    public function targetFieldIsPrefilledWithConfiguredDefaultTarget(string $fixture): void
    {
        $this->importCSVDataSet($fixture);
        $subject = $this->get(BrowseLinksController::class);

        $content = (string)$subject->mainAction($this->createLinkBrowserRequest())->getBody();

        self::assertStringContainsString(
            '<input id="ltarget" type="text" name="ltarget" class="form-control" value="_blank" />',
            $content
        );
    }

    /**
     * An existing link keeps its own target, the configured default does
     * not replace it.
     */
    #[DataProvider('defaultTargetFixturesDataProvider')]
    #[Test]
    public function targetFieldKeepsTargetOfCurrentLinkOverConfiguredDefaultTarget(string $fixture): void
    {
        $this->importCSVDataSet($fixture);
        $subject = $this->get(BrowseLinksController::class);

        $request = $this->createLinkBrowserRequest(['url' => 't3://page?uid=1', 'target' => '_top']);
        $content = (string)$subject->mainAction($request)->getBody();

        self::assertStringContainsString(
            '<input id="ltarget" type="text" name="ltarget" class="form-control" value="_top" />',
            $content
        );
    }

    /**
     * The default only applies to new links. The CKEditor passes only the
     * attributes an existing link carries, so a link without a target
     * arrives without one and has to stay that way, otherwise editing it
     * would add the default target unasked.
     */
    #[DataProvider('defaultTargetFixturesDataProvider')]
    #[Test]
    public function targetFieldIsEmptyForCurrentLinkWithoutTarget(string $fixture): void
    {
        $this->importCSVDataSet($fixture);
        $subject = $this->get(BrowseLinksController::class);

        $request = $this->createLinkBrowserRequest(['url' => 't3://page?uid=1']);
        $content = (string)$subject->mainAction($request)->getBody();

        self::assertStringContainsString(
            '<input id="ltarget" type="text" name="ltarget" class="form-control" value="" />',
            $content
        );
    }

    /**
     * @param array<string, string> $currentLink link and attributes as the CKEditor passes them in P[curUrl]
     */
    private function createLinkBrowserRequest(array $currentLink = []): ServerRequest
    {
        $parameters = [
            'table' => 'tt_content',
            'uid' => '1',
            'fieldName' => 'bodytext',
            'recordType' => 'text',
            'pid' => '1',
            'richtextConfigurationName' => '',
        ];
        if ($currentLink !== []) {
            $parameters['curUrl'] = $currentLink;
        }
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/main', [
                'packageName' => 'typo3/cms-rte-ckeditor',
                '_identifier' => 'main',
            ]))
            ->withQueryParams([
                'editorId' => 'cke_1',
                'contentsLanguage' => 'en',
                'P' => $parameters,
            ]);
        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }
}
