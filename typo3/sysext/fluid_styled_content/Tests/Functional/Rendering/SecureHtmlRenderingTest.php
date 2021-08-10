<?php

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

namespace TYPO3\CMS\FluidStyledContent\Tests\Functional\Rendering;

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\AbstractInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\TypoScriptInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalResponse;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SecureHtmlRenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const ENCRYPTION_KEY = '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6';

    private const TYPO3_CONF_VARS = [
        'SYS' => [
            'encryptionKey' => self::ENCRYPTION_KEY,
        ],
    ];

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    /**
     * @var InternalRequestContext
     */
    private $internalRequestContext;

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = ['fluid_styled_content'];

    /**
     * @var string[]
     */
    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/AdditionalConfiguration.php' => 'typo3conf/AdditionalConfiguration.php',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::initializeDatabaseSnapshot();
    }

    public static function tearDownAfterClass(): void
    {
        static::destroyDatabaseSnapshot();
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // these settings are forwarded to the frontend sub-request as well
        $this->internalRequestContext = (new InternalRequestContext())
            ->withGlobalSettings(['TYPO3_CONF_VARS' => static::TYPO3_CONF_VARS]);

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
            [$this->buildDefaultLanguageConfiguration('EN', 'https://acme.us/')]
        );

        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
        });
    }

    protected function setUpDatabase()
    {
        $backendUser = $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $scenarioFile = __DIR__ . '/Fixtures/SecureHtmlScenario.yaml';
        $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
        $writer = DataHandlerWriter::withBackendUser($backendUser);
        $writer->invokeFactory($factory);
        static::failIfArrayIsNotEmpty(
            $writer->getErrors()
        );

        $this->setUpFrontendRootPage(
            1000,
            [
                'constants' => ['EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript'],
                'setup' => ['EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript'],
            ],
            [
                'title' => 'ACME Root',
            ]
        );
    }

    protected function tearDown(): void
    {
        unset($this->internalRequestContext);
        parent::tearDown();
    }

    public function parseFuncAvoidCrossSiteScriptingDataProvider(): array
    {
        return [
            '#01' => [
                '01: <script>alert(1)</script>',
                '<p>01: &lt;script&gt;alert(1)&lt;/script&gt;</p>',
            ],
            '#02' => [
                '02: <unknown a="a" b="b">value</unknown>',
                '<p>02: &lt;unknown a="a" b="b"&gt;value&lt;/unknown&gt;</p>',
            ],
            '#03' => [
                '03: <img img="img" alt="alt" onerror="alert(1)">',
                '<p>03: <img alt="alt"></p>',
            ],
            '#04' => [
                '04: <img src="img" alt="alt" onerror="alert(1)">',
                '<p>04: <img src="img" alt="alt"></p>',
            ],
            '#05' => [
                '05: <img/src="img"/onerror="alert(1)">',
                '<p>05: &lt;img/src="img"/onerror="alert(1)"&gt;</p>',
            ],
            '#06' => [
                '06: <strong>Given that x < y and y > z...</strong>',
                '<p>06: <strong>Given that x &lt; y and y &gt; z...</strong></p>',
            ],
            '#07' => [
                '07: <a href="t3://page?uid=1000" target="_blank" rel="noreferrer" class="button" role="button" onmouseover="alert(1)">TYPO3</a>',
                '<p>07: <a href="/" target="_blank" rel="noreferrer" class="button" role="button">TYPO3</a></p>',
            ],
        ];
    }

    /**
     * @param string $payload
     * @param string$expectation
     * @test
     * @dataProvider parseFuncAvoidCrossSiteScriptingDataProvider
     */
    public function parseFuncAvoidCrossSiteScripting(string $payload, string $expectation)
    {
        $instructions = [
            $this->createTextContentObject($payload),
        ];
        $response = $this->invokeFrontendRendering(...$instructions);
        self::assertSame($expectation, (string)$response->getBody());
    }

    /**
     * @param AbstractInstruction ...$instructions
     * @return InternalResponse
     */
    private function invokeFrontendRendering(AbstractInstruction ...$instructions): InternalResponse
    {
        $sourcePageId = 1100;

        $request = (new InternalRequest('https://acme.us/'))
            ->withPageId($sourcePageId)
            ->withInstructions(
                [
                    $this->createDefaultInstruction(),
                ]
            );

        if (count($instructions) > 0) {
            $request = $this->applyInstructions($request, ...$instructions);
        }

        return $this->executeFrontendRequest($request, $this->internalRequestContext);
    }

    private function createDefaultInstruction(): TypoScriptInstruction
    {
        return (new TypoScriptInstruction(TemplateService::class))
            ->withTypoScript([
                'config.' => [
                    'no_cache' => 1,
                    'debug' => 0,
                    'admPanel' => 0,
                    'disableAllHeaderCode' => 1,
                    'sendCacheHeaders' => 0,
                ],
                'page' => 'PAGE',
                'page.' => [
                    'typeNum' => 0,
                ]
            ]);
    }

    private function createTextContentObject(string $value): TypoScriptInstruction
    {
        return (new TypoScriptInstruction(TemplateService::class))
            ->withTypoScript([
                'page.' => [
                    '10' => 'TEXT',
                    '10.' => [
                        'value' => $value,
                        'parseFunc' => '< lib.parseFunc_RTE',
                    ],
                ],
            ]);
    }

    /**
     * @param array $parseFunc
     * @return TypoScriptInstruction
     */
    private function createParseFuncInstruction(array $parseFunc): TypoScriptInstruction
    {
        return (new TypoScriptInstruction(TemplateService::class))
            ->withTypoScript([
                'lib.' => [
                    'parseFunc.' => array_replace_recursive(
                        [
                            'makelinks' => 1,
                            'makelinks.' => [
                                'http.' => [
                                    'keep' => 'path',
                                    'extTarget' => '_blank',
                                ],
                                'mailto.' => [
                                    'keep' => 'path',
                                ],
                            ],
                            'allowTags' => '',
                            'denyTags' => '',
                            'constants' => 1,
                            'nonTypoTagStdWrap.' => [
                                'HTMLparser' => 1,
                                'HTMLparser.' => [
                                    'keepNonMatchedTags' => 1,
                                    'htmlSpecialChars' => 2,
                                ],
                            ],
                        ],
                        $parseFunc
                    ),
                ],
            ]);
    }
}
