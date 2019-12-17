<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for build URLs with TypoLink via Frontend Request.
 */
class TypoLinkGeneratorTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = ['frontend', 'workspaces'];

    /**
     * @var string[]
     */
    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/AdditionalConfiguration.php' => 'typo3conf/AdditionalConfiguration.php',
    ];

    protected $pathsToProvideInTestInstance = [
        'typo3/sysext/backend/Resources/Public/Images/Logo.png' => 'fileadmin/logo.png'
    ];

    protected function setUp()
    {
        parent::setUp();

        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/TypoLinkScenario.xml');
        $this->setUpBackendUserFromFixture(1);
        $this->setUpFileStorage();
        $this->setUpFrontendRootPage(
            1000,
            [
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/TypoLinkGenerator.typoscript',
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
                '<a href="/fileadmin/logo.png">fileadmin/logo.png</a>',
            ],
            [
                't3://file?identifier=1:/logo.png&other=other#other',
                '<a href="/fileadmin/logo.png">fileadmin/logo.png</a>',
            ],
            [
                't3://file?identifier=fileadmin/logo.png&other=other#other',
                '<a href="/fileadmin/logo.png">fileadmin/logo.png</a>',
            ],
            [
                't3://folder?identifier=fileadmin&other=other#other',
                '<a href="/fileadmin/">fileadmin/</a>',
            ],
            [
                't3://page?uid=1200&type=1&param-a=a&param-b=b#fragment',
                '<a href="/index.php?id=1200&amp;type=1&amp;param-a=a&amp;param-b=b&amp;cHash=cd025eb18f2cb1fc578ab2273dbb137a#fragment">EN: Features</a>',
            ],
            [
                't3://record?identifier=content&uid=10001&other=other#fragment',
                '<a href="/index.php?id=1200#c10001">EN: Features</a>',
            ],
            [
                't3://url?url=https://typo3.org%3f%26param-a=a%26param-b=b&other=other#other',
                '<a href="https://typo3.org?&amp;param-a=a&amp;param-b=b">https://typo3.org?&amp;param-a=a&amp;param-b=b</a>',
            ],
            [
                '1200,1 target class title &param-a=a',
                '<a href="/index.php?id=1200&amp;type=1&amp;param-a=a&amp;cHash=c51665e6be366043d32971eeecca9495" title="title" target="target" class="class">EN: Features</a>'
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
        $this->assignTypoScriptConstant('typolink.parameter', $parameter, 1000);
        $response = $this->getFrontendResponse(1100);
        static::assertSame($expectation, $response->getContent());
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
        $this->assignTypoScriptConstant('typolink.parameter', $parameter, 1000);
        $response = $this->getFrontendResponse(1100);
        static::assertSame($expectation, $response->getContent());
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
        $this->setUpFrontendRootPage(
            1000,
            [
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/TypoLinkGenerator.typoscript',
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/TypoLinkGenerator.libParseFunc.typoscript',
            ]
        );
        $this->assignTypoScriptConstant('typolink.parameter', $parameter, 1000);
        $response = $this->getFrontendResponse(1100);
        static::assertSame($expectation, $response->getContent());
    }

    /**
     * @return array
     */
    public function linkToPageIsProcessedDataProvider(): array
    {
        return [
            [
                't3://page?uid=9911',
                '<a href="/index.php?id=9911">&lt;good&gt;</a>',
                false,
            ],
            [
                't3://page?uid=9911',
                '<a href="/index.php?id=9911"><good></a>',
                true,
            ],
            [
                't3://page?uid=9912',
                '<a href="/index.php?id=9912">&lt;good a=&quot;a/&quot; b=&quot;thing(1)&quot;&gt;</a>',
                false,
            ],
            [
                't3://page?uid=9912',
                '<a href="/index.php?id=9912"><good></a>',
                true,
            ],
            [
                't3://page?uid=9913',
                '<a href="/index.php?id=9913">&lt;good%20a=&quot;a/&quot;%20b=&quot;thing(1)&quot;&gt;</a>',
                false,
            ],
            [
                't3://page?uid=9913',
                '<a href="/index.php?id=9913">&lt;good%20a=&quot;a/&quot;%20b=&quot;thing(1)&quot;&gt;</a>',
                true,
            ],
            [
                't3://page?uid=9914',
                '<a href="/index.php?id=9914">&lt;good/a=&quot;a/&quot;/b=&quot;thing(1)&quot;&gt;</a>',
                false,
            ],
            [
                't3://page?uid=9914',
                '<a href="/index.php?id=9914">&lt;good/a=&quot;a/&quot;/b=&quot;thing(1)&quot;&gt;</a>',
                true,
            ],
            [
                't3://page?uid=9921',
                '<a href="/index.php?id=9921">&lt;bad&gt;</a>',
                false,
            ],
            [
                't3://page?uid=9921',
                '<a href="/index.php?id=9921">&lt;bad&gt;</a>',
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
        $typoScriptFiles = ['typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/TypoLinkGenerator.typoscript'];
        if ($parseFuncEnabled) {
            $typoScriptFiles[] = 'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/TypoLinkGenerator.libParseFunc.typoscript';
        }

        $this->setUpFrontendRootPage(1000, $typoScriptFiles);
        $this->assignTypoScriptConstant('typolink.parameter', $parameter, 1000);
        $response = $this->getFrontendResponse(1100);
        static::assertSame($expectation, $response->getContent());
    }

    /**
     * @param string $name
     * @param string $value
     * @param int $pageId
     */
    private function assignTypoScriptConstant(string $name, string $value, int $pageId)
    {
        /** @var \TYPO3\CMS\Core\Database\Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_template');
        $connection->update(
            'sys_template',
            ['constants' => sprintf("%s = %s\n", $name, $value)],
            ['pid' => $pageId]
        );
    }

    /**
     * Generates key names based on a template and array items as arguments.
     *
     * + keysFromTemplate([[1, 2, 3], [11, 22, 33]], '%1$d->%2$d (user:%3$d)')
     * + returns the following array with generated keys
     *   [
     *     '1->2 (user:3)'    => [1, 2, 3],
     *     '11->22 (user:33)' => [11, 22, 33],
     *   ]
     *
     * @param array $array
     * @param string $template
     * @param callable|null $callback
     * @return array
     */
    private function keysFromTemplate(array $array, string $template, callable $callback = null): array
    {
        $keys = array_unique(
            array_map(
                function (array $values) use ($template, $callback) {
                    if ($callback !== null) {
                        $values = call_user_func($callback, $values);
                    }
                    return vsprintf($template, $values);
                },
                $array
            )
        );

        if (count($keys) !== count($array)) {
            throw new \LogicException(
                'Amount of generated keys does not match to item count.',
                1534682840
            );
        }

        return array_combine($keys, $array);
    }
}
