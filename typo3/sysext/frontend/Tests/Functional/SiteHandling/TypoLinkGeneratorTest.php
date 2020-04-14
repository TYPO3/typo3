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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Fixtures\LinkHandlingController;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\AbstractInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\ArrayValueInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\TypoScriptInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalResponse;

/**
 * Test case for build URLs with TypoLink via Frontend Request.
 */
class TypoLinkGeneratorTest extends AbstractTestCase
{
    /**
     * @var string
     */
    private $siteTitle = 'A Company that Manufactures Everything Inc';

    /**
     * @var InternalRequestContext
     */
    private $internalRequestContext;

    protected $pathsToProvideInTestInstance = [
        'typo3/sysext/backend/Resources/Public/Images/Logo.png' => 'fileadmin/logo.png'
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
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://acme.us/'),
                $this->buildLanguageConfiguration('FR', 'https://acme.fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://acme.ca/', ['FR', 'EN']),
            ]
        );

        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
        });
    }

    protected function setUpDatabase()
    {
        $backendUser = $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $scenarioFile = __DIR__ . '/Fixtures/TypoLinkScenario.yaml';
        $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
        $writer = DataHandlerWriter::withBackendUser($backendUser);
        $writer->invokeFactory($factory);
        static::failIfArrayIsNotEmpty(
            $writer->getErrors()
        );

        // @todo Provide functionality of assigning TSconfig to Testing Framework
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages');
        /** @var $connection \TYPO3\CMS\Core\Database\Connection */
        $connection->update(
            'pages',
            ['TSconfig' => implode(chr(10), [
                'TCEMAIN.linkHandler.content {',
                '   configuration.table = tt_content',
                '}',
            ])],
            ['uid' => 1000]
        );

        $this->setUpFileStorage();
        $this->setUpFrontendRootPage(
            1000,
            [
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkGenerator.typoscript',
            ],
            [
                'title' => 'ACME Root',
                'sitetitle' => $this->siteTitle,
            ]
        );
    }

    /**
     * @todo Provide functionality of creating and indexing fileadmin/ in Testing Framework
     */
    private function setUpFileStorage()
    {
        $storageRepository = new StorageRepository();
        $storageId = $storageRepository->createLocalStorage(
            'fileadmin/ (auto-created)',
            'fileadmin/',
            'relative',
            'Default storage created in TypoLinkTest',
            true
        );
        $storage = $storageRepository->findByUid($storageId);
        (new Indexer($storage))->processChangesInStorages();
    }

    protected function tearDown(): void
    {
        unset($this->internalRequestContext);
        parent::tearDown();
    }

    /**
     * @return array
     */
    public function linkIsGeneratedDataProvider(): array
    {
        $instructions = [
            [
                't3://email?email=mailto:user@example.org&other=other#other',
                '<a href="mailto:user@example.org">user@example.org</a>',
            ],
            [
                't3://email?email=user@example.org&other=other#other',
                '<a href="mailto:user@example.org">user@example.org</a>',
            ],
            [
                't3://email?email=user@example.org?subject=Hello%20World#other',
                '<a href="mailto:user@example.org?subject=Hello World">user@example.org?subject=Hello World</a>',
            ],
            [
                't3://file?uid=1&type=1&other=other#other',
                '<a href="/fileadmin/logo.png#other">fileadmin/logo.png</a>',
            ],
            [
                't3://file?identifier=1:/logo.png&other=other#other',
                '<a href="/fileadmin/logo.png#other">fileadmin/logo.png</a>',
            ],
            [
                't3://file?identifier=fileadmin/logo.png&other=other#other',
                '<a href="/fileadmin/logo.png#other">fileadmin/logo.png</a>',
            ],
            [
                't3://folder?identifier=fileadmin&other=other#other',
                '<a href="/fileadmin/#other">fileadmin/</a>',
            ],
            [
                't3://page?uid=1200&type=1&param-a=a&param-b=b#fragment',
                '<a href="/features?param-a=a&amp;param-b=b&amp;type=1&amp;cHash=92aa5284d0ad18f7934fe94b52f6c1a5#fragment">EN: Features</a>',
            ],
            [
                't3://page?uid=1300&additional=1&param-a=a#fragment',
                '<a href="http://typo3.org">Go to TYPO3.org</a>',
            ],
            [
                't3://record?identifier=content&uid=10001&other=other#fragment',
                '<a href="/features#c10001">EN: Features</a>',
            ],
            [
                't3://url?url=https://typo3.org%3f%26param-a=a%26param-b=b&other=other#other',
                '<a href="https://typo3.org?&amp;param-a=a&amp;param-b=b">https://typo3.org?&amp;param-a=a&amp;param-b=b</a>',
            ],
            [
                '1200,1 target class title &param-a=a',
                '<a href="/features?param-a=a&amp;type=1&amp;cHash=62ac35c73f425af5e13cfff14c04424e" title="title" target="target" class="class">EN: Features</a>'
            ],
            [
                'user@example.org target class title &other=other',
                '<a href="mailto:user@example.org" title="title" target="target" class="class">user@example.org</a>'
            ],
        ];
        return $this->keysFromTemplate($instructions, '%1$s;');
    }

    /**
     * @param string $parameter
     * @param string $expectation
     *
     * @test
     * @dataProvider linkIsGeneratedDataProvider
     */
    public function linkIsGenerated(string $parameter, string $expectation)
    {
        $response = $this->invokeTypoLink($parameter);
        self::assertSame($expectation, (string)$response->getBody());
    }

    /**
     * @return array
     */
    public function linkIsEncodedDataProvider(): array
    {
        $instructions = [
            [
                't3://email?email=mailto:<bad>thing(1)</bad>',
                '<a href="mailto:&lt;bad&gt;thing(1)&lt;/bad&gt;">&lt;bad&gt;thing(1)&lt;/bad&gt;</a>',
            ],
            [
                't3://email?email=mailto:<good%20a="a/"%20b="thing(1)">',
                '<a href="mailto:&lt;good a=&quot;a/&quot; b=&quot;thing(1)&quot;&gt;">&lt;good a=&quot;a/&quot; b=&quot;thing(1)&quot;&gt;</a>',
            ],
            [
                't3://email?email=<bad>thing(1)</bad>',
                '<a href="mailto:&lt;bad&gt;thing(1)&lt;/bad&gt;">&lt;bad&gt;thing(1)&lt;/bad&gt;</a>',
            ],
            [
                't3://email?email=<good%20a="a/"%20b="thing(1)">',
                '<a href="mailto:&lt;good a=&quot;a/&quot; b=&quot;thing(1)&quot;&gt;">&lt;good a=&quot;a/&quot; b=&quot;thing(1)&quot;&gt;</a>',
            ],
            [
                't3://folder?identifier=<any>',
                '',
            ],
            [
                't3://page?uid=<any>',
                '',
            ],
            [
                't3://record?identifier=content&uid=<any>',
                '',
            ],
            [
                't3://url?url=<bad>thing(1)</bad>',
                '<a href="http://&lt;bad&gt;thing(1)&lt;/bad&gt;">http://&lt;bad&gt;thing(1)&lt;/bad&gt;</a>'
            ],
            [
                't3://url?url=<good%20a="a/"%20b="thing(1)">',
                '<a href="http://&lt;good a=&quot;a/&quot; b=&quot;thing(1)&quot;&gt;">http://&lt;good a=&quot;a/&quot; b=&quot;thing(1)&quot;&gt;</a>'
            ],
            [
                't3://url?url=javascript:good()',
                '<a ></a>'
            ],
            [
                "t3://url?url=java\tscript:good()",
                '<a href="http://java_script:good()">http://java_script:good()</a>'
            ],
            [
                't3://url?url=java&#09;script:good()',
                '<a href="http://java">http://java</a>'
            ],
            [
                't3://url?url=javascript&colon;good()',
                '<a href="http://javascript">http://javascript</a>'
            ],
            [
                't3://url?url=data:text/html,<good>',
                '<a ></a>'
            ],
            [
                "t3://url?url=da\tsta:text/html,<good>",
                '<a href="http://da_sta:text/html,&lt;good&gt;">http://da_sta:text/html,&lt;good&gt;</a>'
            ],
            [
                't3://url?url=da&#09;ta:text/html,<good>',
                '<a href="http://da">http://da</a>'
            ],
            [
                't3://url?url=data&colon;text/html,<good>',
                '<a href="http://data">http://data</a>'
            ],
            [
                't3://url?url=%26%23106%3B%26%2397%3B%26%23118%3B%26%2397%3B%26%23115%3B%26%2399%3B%26%23114%3B%26%23105%3B%26%23112%3B%26%23116%3B%26%2358%3B%26%23103%3B%26%23111%3B%26%23111%3B%26%23100%3B%26%2340%3B%26%2341%3B',
                '<a href="http://&amp;#106;&amp;#97;&amp;#118;&amp;#97;&amp;#115;&amp;#99;&amp;#114;&amp;#105;&amp;#112;&amp;#116;&amp;#58;&amp;#103;&amp;#111;&amp;#111;&amp;#100;&amp;#40;&amp;#41;">http://&amp;#106;&amp;#97;&amp;#118;&amp;#97;&amp;#115;&amp;#99;&amp;#114;&amp;#105;&amp;#112;&amp;#116;&amp;#58;&amp;#103;&amp;#111;&amp;#111;&amp;#100;&amp;#40;&amp;#41;</a>',
            ],
            [
                '<bad>thing(1)</bad>',
                '<a href="/&lt;bad&gt;thing(1)&lt;/bad&gt;">&lt;bad&gt;thing(1)&lt;/bad&gt;</a>'
            ],
            [
                '<good%20a="a/"%20b="thing(1)">',
                '<a href="/&lt;good%20a=&quot;a/&quot;%20b=&quot;thing(1)&quot;&gt;">&lt;good a=&quot;a/&quot; b=&quot;thing(1)&quot;&gt;</a>'
            ],
            [
                '<good/a="a/"/b="thing(1)"> target class title &other=other',
                '<a href="/&lt;good/a=&quot;a/&quot;/b=&quot;thing(1)&quot;&gt;" title="title" target="target" class="class">&lt;good/a=&quot;a/&quot;/b=&quot;thing(1)&quot;&gt;</a>'
            ],
            [
                'javascript:good()',
                '',
            ],
            [
                "java\tscript:good()",
                '',
            ],
            [
                'java&#09;script:good()',
                '<a href="java&amp;#09;script:good()"></a>'
            ],
            [
                'javascript&colon;good()',
                ''
            ],
            [
                'data:text/html,<good>',
                '',
            ],
            [
                "da\tta:text/html,<good>",
                '',
            ],
            [
                'da&#09;ta:text/html,<good>',
                '<a href="da&amp;#09;ta:text/html,&lt;good&gt;"></a>',
            ],
            [
                'data&colon;text/html,<good>',
                '<a href="/data&amp;colon;text/html,&lt;good&gt;">data&amp;colon;text/html,&lt;good&gt;</a>',
            ],
            [
                '%26%23106%3B%26%2397%3B%26%23118%3B%26%2397%3B%26%23115%3B%26%2399%3B%26%23114%3B%26%23105%3B%26%23112%3B%26%23116%3B%26%2358%3B%26%23103%3B%26%23111%3B%26%23111%3B%26%23100%3B%26%2340%3B%26%2341%3B',
                '',
            ],
            [
                '</> <"> <"> <">',
                '<a href="/&lt;/&gt;" title="&lt;&quot;&gt;" target="&lt;&quot;&gt;" class="&lt;&quot;&gt;">&lt;/&gt;</a>',
            ],
        ];
        return $this->keysFromTemplate($instructions, '%1$s;');
    }

    /**
     * @param string $parameter
     * @param string $expectation
     *
     * @test
     * @dataProvider linkIsEncodedDataProvider
     */
    public function linkIsEncodedPerDefault(string $parameter, string $expectation)
    {
        $response = $this->invokeTypoLink($parameter);
        self::assertSame($expectation, (string)$response->getBody());
    }

    /**
     * @param string $parameter
     * @param string $expectation
     *
     * @test
     * @dataProvider linkIsEncodedDataProvider
     */
    public function linkIsEncodedHavingParseFunc(string $parameter, string $expectation)
    {
        $response = $this->invokeTypoLink($parameter, $this->createParseFuncInstruction([
            'allowTags' => 'good',
            'denyTags' => '*',
            'nonTypoTagStdWrap.' => [
                'HTMLparser.' => [
                    'tags.' => [
                        'good.' => [
                            'allowedAttribs' => 0,
                        ],
                    ],
                ],
            ],
        ]));
        self::assertSame($expectation, (string)$response->getBody());
    }

    /**
     * @return array
     */
    public function linkToPageIsProcessedDataProvider(): array
    {
        return [
            [
                't3://page?uid=9911',
                '<a href="/test/good">&lt;good&gt;</a>',
                false,
            ],
            [
                't3://page?uid=9911',
                '<a href="/test/good"><good></a>',
                true,
            ],
            [
                't3://page?uid=9912',
                '<a href="/test/good-a-b-spaced">&lt;good a=&quot;a/&quot; b=&quot;thing(1)&quot;&gt;</a>',
                false,
            ],
            [
                't3://page?uid=9912',
                '<a href="/test/good-a-b-spaced"><good></a>',
                true,
            ],
            [
                't3://page?uid=9913',
                '<a href="/test/good-a-b-enc-a">&lt;good%20a=&quot;a/&quot;%20b=&quot;thing(1)&quot;&gt;</a>',
                false,
            ],
            [
                't3://page?uid=9913',
                '<a href="/test/good-a-b-enc-a">&lt;good%20a=&quot;a/&quot;%20b=&quot;thing(1)&quot;&gt;</a>',
                true,
            ],
            [
                't3://page?uid=9914',
                '<a href="/test/good-a-b-enc-b">&lt;good/a=&quot;a/&quot;/b=&quot;thing(1)&quot;&gt;</a>',
                false,
            ],
            [
                't3://page?uid=9914',
                '<a href="/test/good-a-b-enc-b">&lt;good/a=&quot;a/&quot;/b=&quot;thing(1)&quot;&gt;</a>',
                true,
            ],
            [
                't3://page?uid=9921',
                '<a href="/test/bad">&lt;bad&gt;</a>',
                false,
            ],
            [
                't3://page?uid=9921',
                '<a href="/test/bad">&lt;bad&gt;</a>',
                true,
            ],
        ];
    }

    /**
     * @param string $parameter
     * @param string $expectation
     * @param bool $parseFuncEnabled
     *
     * @test
     * @dataProvider linkToPageIsProcessedDataProvider
     */
    public function linkToPageIsProcessed(string $parameter, string $expectation, bool $parseFuncEnabled)
    {
        $instructions = [];
        if ($parseFuncEnabled) {
            $instructions[] = $this->createParseFuncInstruction([
                'allowTags' => 'good',
                'denyTags' => '*',
                'nonTypoTagStdWrap.' => [
                    'HTMLparser.' => [
                        'tags.' => [
                            'good.' => [
                                'allowedAttribs' => 0,
                            ],
                        ],
                    ],
                ],
            ]);
        }
        $response = $this->invokeTypoLink($parameter, ...$instructions);
        self::assertSame($expectation, (string)$response->getBody());
    }

    /**
     * @param string $parameter
     * @param AbstractInstruction ...$instructions
     * @return InternalResponse
     */
    private function invokeTypoLink(string $parameter, AbstractInstruction ...$instructions): InternalResponse
    {
        $sourcePageId = 1100;
        $targetPageId = 1200;

        $request = (new InternalRequest('https://acme.us/'))
            ->withPageId($sourcePageId)
            ->withInstructions(
                [
                    $this->createRecordLinksInstruction([
                        'parameter' => $targetPageId,
                        'section.' => [
                            'data' => 'field:uid',
                            'wrap' => 'c|',
                        ],
                    ]),
                    $this->createTypoLinkInstruction([
                        'parameter' => $parameter,
                    ])
                ]
            );

        if (count($instructions) > 0) {
            $request = $this->applyInstructions($request, ...$instructions);
        }

        return $this->executeFrontendRequest($request, $this->internalRequestContext);
    }

    /**
     * @param array $typoLink
     * @return ArrayValueInstruction
     */
    private function createTypoLinkInstruction(array $typoLink): ArrayValueInstruction
    {
        return (new ArrayValueInstruction(LinkHandlingController::class))
            ->withArray([
                '10' => 'TEXT',
                '10.' => [
                    'typolink.' => $typoLink
                ]
            ]);
    }

    /**
     * @param array $typoLink
     * @return TypoScriptInstruction
     */
    private function createRecordLinksInstruction(array $typoLink): TypoScriptInstruction
    {
        return (new TypoScriptInstruction(TemplateService::class))
            ->withTypoScript([
                'config.' => [
                    'recordLinks.' => [
                        'content.' => [
                            'forceLink' => 1,
                            'typolink.' => $typoLink,
                        ],
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

    /**
     * @param InternalRequest $request
     * @param AbstractInstruction ...$instructions
     * @return InternalRequest
     *
     * @todo Instruction handling should be part of Testing Framework (multiple instructions per identifier, merge in interface)
     */
    private function applyInstructions(InternalRequest $request, AbstractInstruction ...$instructions): InternalRequest
    {
        $modifiedInstructions = [];

        foreach ($instructions as $instruction) {
            $identifier = $instruction->getIdentifier();
            if (isset($modifiedInstructions[$identifier]) || $request->getInstruction($identifier) !== null) {
                $modifiedInstructions[$identifier] = $this->mergeInstruction(
                    $modifiedInstructions[$identifier] ?? $request->getInstruction($identifier),
                    $instruction
                );
            } else {
                $modifiedInstructions[$identifier] = $instruction;
            }
        }

        return $request->withInstructions($modifiedInstructions);
    }

    /**
     * @param AbstractInstruction $current
     * @param AbstractInstruction $other
     * @return AbstractInstruction
     */
    private function mergeInstruction(AbstractInstruction $current, AbstractInstruction $other): AbstractInstruction
    {
        if (get_class($current) !== get_class($other)) {
            throw new \LogicException('Cannot merge different instruction types', 1565863174);
        }

        if ($current instanceof TypoScriptInstruction) {
            /** @var $other TypoScriptInstruction */
            $typoScript = array_replace_recursive(
                $current->getTypoScript() ?? [],
                $other->getTypoScript() ?? []
            );
            $constants = array_replace_recursive(
                $current->getConstants() ?? [],
                $other->getConstants() ?? []
            );
            if ($typoScript !== []) {
                $current = $current->withTypoScript($typoScript);
            }
            if ($constants !== []) {
                $current = $current->withConstants($constants);
            }
            return $current;
        }

        if ($current instanceof ArrayValueInstruction) {
            /** @var $other ArrayValueInstruction */
            $array = array_merge_recursive($current->getArray(), $other->getArray());
            return $current->withArray($array);
        }

        return $current;
    }
}
