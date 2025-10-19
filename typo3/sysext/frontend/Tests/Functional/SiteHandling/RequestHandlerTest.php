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
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Cache\NonceValueSubstitution;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\TypoScriptInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

final class RequestHandlerTest extends AbstractTestCase
{
    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                // `typo3/testing-framework` uses `NullBackend` per default
                'cacheConfigurations' => [
                    'pages' => [
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                    'hash' => [
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                ],
            ],
        ],
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/frontend/Tests/Functional/Fixtures/Assets/app.css' => 'fileadmin/app.css',
        'typo3/sysext/frontend/Tests/Functional/Fixtures/Assets/app.js' => 'fileadmin/app.js',
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance['FE']['debug'] = true;
        parent::setUp();

        $this->withDatabaseSnapshot(function () {
            $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
            $backendUser = $this->setUpBackendUser(1);
            $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
            $scenarioFile = __DIR__ . '/Fixtures/SlugScenario.yaml';
            $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
            $writer = DataHandlerWriter::withBackendUser($backendUser);
            $writer->invokeFactory($factory);
            static::failIfArrayIsNotEmpty($writer->getErrors());
            $this->setUpFrontendRootPage(
                1000,
                ['EXT:frontend/Tests/Functional/SiteHandling/Fixtures/RequestHandler.typoscript'],
                ['title' => 'ACME Root']
            );
            $this->setUpFrontendRootPage(
                1200,
                ['EXT:frontend/Tests/Functional/SiteHandling/Fixtures/RequestHandler.typoscript'],
                ['title' => 'ACME Features']
            );
        });
        $this->writeSiteConfiguration(
            'website-default',
            $this->buildSiteConfiguration(1000, 'https://website.local/default/'),
        );
        $this->writeSiteConfiguration(
            'website-csp-enabled',
            $this->buildSiteConfiguration(1200, 'https://website.local/csp-enabled/'),
            csp: [
                'enforce' => true,
                'report' => true,
            ],
        );
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    #[Test]
    public function nonceAttributesAreNotAssignedWhenCspIsDisabled(): void
    {
        $response = $this->executeFrontendSubRequest(new InternalRequest('https://website.local/default/welcome'));
        $dom = new \DOMDocument();
        $dom->loadHTML((string)$response->getBody());
        $xpath = new \DOMXPath($dom);
        $nonceAttrs = $xpath->query('//*[@nonce]');

        self::assertStringStartsWith('max-age=', $response->getHeaderLine('Cache-Control'));
        self::assertSame('public', $response->getHeaderLine('Pragma'));
        self::assertEmpty($response->getHeaderLine('Content-Security-Policy'));
        self::assertCount(0, $nonceAttrs);
    }

    #[Test]
    public function nonceAttributesForAssetsAreUpdatedInCachedState(): void
    {
        $internalRequest = new InternalRequest('https://website.local/csp-enabled/features');
        $firstResponse = $this->executeFrontendSubRequest($internalRequest);
        $firstCspHeader = $firstResponse->getHeaderLine('Content-Security-Policy');
        $dom = new \DOMDocument();
        $dom->loadHTML((string)$firstResponse->getBody());
        $xpath = new \DOMXPath($dom);
        $firstScriptNonce = $xpath->query('//script')->item(0)?->attributes->getNamedItem('nonce')?->nodeValue;
        $firstLinkNonce = $xpath->query('//link')->item(0)?->attributes->getNamedItem('nonce')?->nodeValue;

        self::assertNotEmpty($firstScriptNonce);
        self::assertSame($firstScriptNonce, $firstLinkNonce);
        self::assertStringContainsString(sprintf("'nonce-%s'", $firstScriptNonce), $firstCspHeader);
        self::assertEmpty($firstResponse->getHeaderLine('X-TYPO3-Debug-Cache'));
        self::assertNotEmpty($firstResponse->getHeaderLine('Content-Security-Policy-Report-Only'));
        self::assertSame('private, no-store', $firstResponse->getHeaderLine('Cache-Control'));

        $secondResponse = $this->executeFrontendSubRequest($internalRequest);
        $secondCspHeader = $secondResponse->getHeaderLine('Content-Security-Policy');
        $dom = new \DOMDocument();
        $dom->loadHTML((string)$secondResponse->getBody());
        $xpath = new \DOMXPath($dom);
        $secondScriptNonce = $xpath->query('//script')->item(0)?->attributes->getNamedItem('nonce')?->nodeValue;
        $secondLinkNonce = $xpath->query('//link')->item(0)?->attributes->getNamedItem('nonce')?->nodeValue;

        self::assertNotEmpty($secondScriptNonce);
        self::assertSame($secondScriptNonce, $secondLinkNonce);
        self::assertNotSame($firstScriptNonce, $secondScriptNonce);
        self::assertStringContainsString(sprintf("'nonce-%s'", $secondScriptNonce), $secondCspHeader);
        self::assertStringStartsWith('Cached page generated', $secondResponse->getHeaderLine('X-TYPO3-Debug-Cache'));
        self::assertNotEmpty($secondResponse->getHeaderLine('Content-Security-Policy-Report-Only'));
        self::assertSame('private, no-store', $secondResponse->getHeaderLine('Cache-Control'));
    }

    public static function nonceAttributesForAssetsAreUpdatedInUncachedStateDataProvider(): \Generator
    {
        yield 'uncached' => [
            (new TypoScriptInstruction())->withTypoScript([
                'config.' => ['no_cache' => 1],
            ]),
        ];
        yield 'partially cached (static content)' => [
            (new TypoScriptInstruction())->withTypoScript([
                'page.' => [
                    '10' => 'COA_INT',
                    '10.' => [
                        '10' => 'TEXT',
                        '10.' => ['value' => '<p>uncached content</p>'],
                    ],
                ],
            ]),
        ];
        yield 'partially cached (inline JavaScript)' => [
            (new TypoScriptInstruction())->withTypoScript([
                'page.' => [
                    '10' => 'COA_INT',
                    '10.' => [
                        '10' => 'FLUIDTEMPLATE',
                        '10.' => [
                            'template' => 'TEXT',
                            'template.' => [
                                'value' => '<f:asset.script identifier="test" useNonce="true">console.log(true);</f:asset.script>',
                            ],
                        ],
                    ],
                ],
            ]),
        ];
    }

    #[Test]
    #[DataProvider('nonceAttributesForAssetsAreUpdatedInUncachedStateDataProvider')]
    public function nonceAttributesForAssetsAreUpdatedInUncachedState(TypoScriptInstruction $instruction): void
    {
        $internalRequest = new InternalRequest('https://website.local/csp-enabled/features');
        $internalRequest = $internalRequest->withInstructions([$instruction]);
        $firstResponse = $this->executeFrontendSubRequest($internalRequest);
        $firstCspHeader = $firstResponse->getHeaderLine('Content-Security-Policy');
        $dom = new \DOMDocument();
        $dom->loadHTML((string)$firstResponse->getBody());
        $xpath = new \DOMXPath($dom);

        preg_match('/\'nonce-([^\']+)\'/', $firstCspHeader, $matches);
        $firstCspHeaderNonce = $matches[1] ?? null;
        self::assertNotEmpty($firstCspHeaderNonce);

        $firstScripts = $xpath->query('//script');
        $firstLinks = $xpath->query('//link');
        $firstScriptNonce = $firstScripts->item(0)?->attributes->getNamedItem('nonce')?->nodeValue;
        $firstLinkNonce = $firstLinks->item(0)?->attributes->getNamedItem('nonce')?->nodeValue;

        foreach ($firstScripts as $node) {
            self::assertSame($firstCspHeaderNonce, $node->attributes->getNamedItem('nonce')?->nodeValue);
        }
        foreach ($firstLinks as $node) {
            self::assertSame($firstCspHeaderNonce, $node->attributes->getNamedItem('nonce')?->nodeValue);
        }

        self::assertNotEmpty($firstScriptNonce);
        self::assertSame($firstScriptNonce, $firstLinkNonce);
        self::assertEmpty($firstResponse->getHeaderLine('X-TYPO3-Debug-Cache'));
        self::assertNotEmpty($firstResponse->getHeaderLine('Content-Security-Policy-Report-Only'));
        self::assertSame('private, no-store', $firstResponse->getHeaderLine('Cache-Control'));

        $secondResponse = $this->executeFrontendSubRequest($internalRequest);
        $secondCspHeader = $secondResponse->getHeaderLine('Content-Security-Policy');
        $dom = new \DOMDocument();
        $dom->loadHTML((string)$secondResponse->getBody());
        $xpath = new \DOMXPath($dom);

        preg_match('/\'nonce-([^\']+)\'/', $secondCspHeader, $matches);
        $secondCspHeaderNonce = $matches[1] ?? null;
        self::assertNotEmpty($secondCspHeaderNonce);

        $secondScripts = $xpath->query('//script');
        $secondLinks = $xpath->query('//link');
        $secondScriptNonce = $secondScripts->item(0)?->attributes->getNamedItem('nonce')?->nodeValue;
        $secondLinkNonce = $secondLinks->item(0)?->attributes->getNamedItem('nonce')?->nodeValue;

        foreach ($secondScripts as $node) {
            self::assertSame($secondCspHeaderNonce, $node->attributes->getNamedItem('nonce')?->nodeValue);
        }
        foreach ($secondLinks as $node) {
            self::assertSame($secondCspHeaderNonce, $node->attributes->getNamedItem('nonce')?->nodeValue);
        }

        self::assertNotEmpty($secondScriptNonce);
        self::assertSame($secondScriptNonce, $secondLinkNonce);
        self::assertNotSame($firstScriptNonce, $secondScriptNonce);
        self::assertEmpty($firstResponse->getHeaderLine('X-TYPO3-Debug-Cache'));
        self::assertNotEmpty($secondResponse->getHeaderLine('Content-Security-Policy-Report-Only'));
        self::assertSame('private, no-store', $secondResponse->getHeaderLine('Cache-Control'));
    }

    #[Test]
    public function nonceValueSubstitutionIsInvoked(): void
    {
        $nonceValueSubstitutionMock = $this->createMock(NonceValueSubstitution::class);
        $nonceValueSubstitutionMock->expects($this->once())
            ->method('substituteNonce')
            ->with(self::isArray())
            ->willReturnCallback(static fn(array $context) => $context['content'] ?? null);
        GeneralUtility::addInstance(NonceValueSubstitution::class, $nonceValueSubstitutionMock);
        $this->executeFrontendSubRequest(new InternalRequest('https://website.local/csp-enabled/features'));
    }
}
