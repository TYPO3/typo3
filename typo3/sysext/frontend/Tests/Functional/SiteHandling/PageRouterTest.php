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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Routing\Event\AfterPageUriGeneratedEvent;
use TYPO3\CMS\Core\Routing\PageRouter;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;

final class PageRouterTest extends AbstractTestCase
{
    private SiteFinder $siteFinder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siteFinder = $this->get(SiteFinder::class);

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://acme.us/'),
                $this->buildLanguageConfiguration('FR', 'https://acme.fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://acme.ca/', ['FR', 'EN']),
            ]
        );
        $this->withDatabaseSnapshot(
            function () {
                $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
                $backendUser = $this->setUpBackendUser(1);
                $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
                $scenarioFile = __DIR__ . '/Fixtures/SlugScenario.yaml';
                $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
                $writer = DataHandlerWriter::withBackendUser($backendUser);
                $writer->invokeFactory($factory);
                static::failIfArrayIsNotEmpty($writer->getErrors());
            },
            function () {
                $backendUser = $this->setUpBackendUser(1);
                $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
            }
        );
    }

    public static function generateUriSanitizesSuperfluousParametersDataProvider(): \Generator
    {
        $keyValueCHash = '&cHash=00f30ec5eab6013fefccbd5739cc3ca2bf529a043866bea353c7cb4eade8a8d7';
        yield 'none' => [[], 'https://acme.us/'];
        yield 'page-id as id parameter' => [['id' => 1000], 'https://acme.us/'];
        yield 'any value as id parameter' => [['id' => 123], 'https://acme.us/'];
        yield 'page-id as id parameter + other value' => [['id' => 1000, 'key' => 'value'], 'https://acme.us/?key=value' . $keyValueCHash];
        yield 'any value as id parameter + other value' => [['id' => 123, 'key' => 'value'], 'https://acme.us/?key=value' . $keyValueCHash];
    }

    #[Test]
    #[DataProvider('generateUriSanitizesSuperfluousParametersDataProvider')]
    public function generateUriSanitizesSuperfluousParameters(array $parameters, string $expectation): void
    {
        $pageRouter = new PageRouter($this->siteFinder->getSiteByIdentifier('acme-com'));
        $result = $pageRouter->generateUri(1000, $parameters);
        self::assertSame($expectation, (string)$result);
    }

    #[Test]
    public function generateUriDispatchesAfterPageUriGeneratedEvent(): void
    {
        $eventCapture = new class {
            public ?AfterPageUriGeneratedEvent $event = null;
            public ?string $dispatchedUri = null;
        };

        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            'after-page-uri-generated-test-listener',
            static function (AfterPageUriGeneratedEvent $event) use ($eventCapture): void {
                $eventCapture->event = $event;
                $eventCapture->dispatchedUri = (string)$event->getUri();
                $event->setUri(new Uri('https://typo3.org/custom-page'));
            }
        );
        $listenerProvider = $this->get(ListenerProvider::class);
        $listenerProvider->addListener(AfterPageUriGeneratedEvent::class, 'after-page-uri-generated-test-listener');

        GeneralUtility::setSingletonInstance(EventDispatcherInterface::class, new EventDispatcher($listenerProvider));

        $pageRouter = new PageRouter($this->siteFinder->getSiteByIdentifier('acme-com'));
        $result = $pageRouter->generateUri(1000, [], 'my-fragment');

        self::assertSame('https://typo3.org/custom-page', (string)$result);
        self::assertNotNull($eventCapture->event);
        self::assertSame('https://acme.us/#my-fragment', $eventCapture->dispatchedUri);
        self::assertSame(1000, $eventCapture->event->getRoute());
        self::assertSame([], $eventCapture->event->getParameters());
        self::assertSame('my-fragment', $eventCapture->event->getFragment());
        self::assertSame('', $eventCapture->event->getType());
        self::assertSame(0, $eventCapture->event->getLanguage()->getLanguageId());
        self::assertSame('acme-com', $eventCapture->event->getSite()->getIdentifier());
    }
}
