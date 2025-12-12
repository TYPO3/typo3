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

namespace TYPO3\CMS\Redirects\Tests\Functional\RedirectUpdate;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Redirects\Event\SlugRedirectChangeItemCreatedEvent;
use TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItem;
use TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItemFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SlugRedirectChangeItemFactoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['redirects'];

    #[Test]
    public function returnsNullIfNoSiteConfigurationCanBeFound(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysFolderAsRootPage.csv');

        $changeItem = $this->get(SlugRedirectChangeItemFactory::class)->create(1);
        self::assertNull($changeItem);
    }

    #[Test]
    public function returnsNullIfSiteConfigurationFoundButAutoCreateRedirectsAndAutoUpdateSlugsOptionsDisabled(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysFolderAsRootPage.csv');
        $this->buildBaseSite([
            'redirects' => [
                'autoUpdateSlugs' => false,
                'autoCreateRedirects' => false,
                'redirectTTL' => 30,
                'httpStatusCode' => 307,
            ],
        ]);

        $changeItem = $this->get(SlugRedirectChangeItemFactory::class)->create(1);
        self::assertNull($changeItem);
    }

    #[Test]
    public function returnsChangeItemIfSiteConfigurationFoundAndOnlyAutoCreateRedirectsEnabled(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysFolderAsRootPage.csv');
        $this->buildBaseSite([
            'redirects' => [
                'autoUpdateSlugs' => false,
                'autoCreateRedirects' => true,
                'redirectTTL' => 30,
                'httpStatusCode' => 307,
            ],
        ]);

        $changeItem = $this->get(SlugRedirectChangeItemFactory::class)->create(1);
        self::assertInstanceOf(SlugRedirectChangeItem::class, $changeItem);
        self::assertSame(1, $changeItem->getPageId());
        self::assertSame(1, $changeItem->getDefaultLanguagePageId());
    }

    #[Test]
    public function returnsChangeItemIfSiteConfigurationFoundAndOnlyAutoUpdateSlugsEnabled(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysFolderAsRootPage.csv');
        $this->buildBaseSite([
            'redirects' => [
                'autoUpdateSlugs' => true,
                'autoCreateRedirects' => false,
                'redirectTTL' => 30,
                'httpStatusCode' => 307,
            ],
        ]);

        $changeItem = $this->get(SlugRedirectChangeItemFactory::class)->create(1);
        self::assertInstanceOf(SlugRedirectChangeItem::class, $changeItem);
        self::assertSame(1, $changeItem->getPageId());
        self::assertSame(1, $changeItem->getDefaultLanguagePageId());
    }

    #[Test]
    public function slugRedirectChangeItemCreatedEventIsTriggered(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysFolderAsRootPage.csv');
        $this->buildBaseSite([
            'redirects' => [
                'autoUpdateSlugs' => true,
                'autoCreateRedirects' => true,
                'redirectTTL' => 30,
                'httpStatusCode' => 307,
            ],
        ]);

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'slug-redirect-change-item-created',
            static function (SlugRedirectChangeItemCreatedEvent $event) use (
                &$slugRedirectChangeItemCreatedEvent,
                &$modifiedChangeItem
            ) {
                $modifiedChangeItem = $event->getSlugRedirectChangeItem();
                $modifiedChangeItem = $modifiedChangeItem->withChanged(['foobar']);
                $event->setSlugRedirectChangeItem($modifiedChangeItem);
                $slugRedirectChangeItemCreatedEvent = $event;
            }
        );
        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(SlugRedirectChangeItemCreatedEvent::class, 'slug-redirect-change-item-created');

        $changeItem = $this->get(SlugRedirectChangeItemFactory::class)->create(1);
        self::assertInstanceOf(SlugRedirectChangeItemCreatedEvent::class, $slugRedirectChangeItemCreatedEvent);
        self::assertEquals($modifiedChangeItem, $slugRedirectChangeItemCreatedEvent->getSlugRedirectChangeItem());
        self::assertEquals($modifiedChangeItem, $changeItem);
    }

    private function buildBaseSite(array $settings): void
    {
        $configuration = [
            'rootPageId' => 1,
            'base' => '/',
            'settings' => $settings,
        ];
        $siteWriter = $this->get(SiteWriter::class);
        $siteWriter->write('testing', $configuration);
    }
}
