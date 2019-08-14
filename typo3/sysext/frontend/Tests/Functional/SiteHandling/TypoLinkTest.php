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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Fixtures\LinkHandlingController;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\ArrayValueInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\TypoScriptInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

/**
 * Test case for build URLs with TypoLink via Frontend Request.
 */
class TypoLinkTest extends AbstractTestCase
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

        $scenarioFile = __DIR__ . '/Fixtures/SlugScenario.yaml';
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
                '<a href="/features?param-a=a&amp;param-b=b&amp;type=1&amp;cHash=92aa5284d0ad18f7934fe94b52f6c1a5#fragment">EN: Features</a>',
            ],
            [
                't3://record?identifier=content&uid=10001&other=other#fragment',
                '<a href="/features#c10001">EN: Features</a>',
            ],
            [
                't3://url?url=https://typo3.org&other=other#other',
                '<a href="https://typo3.org">https://typo3.org</a>',
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
        $sourcePageId = 1100;

        $response = $this->executeFrontendRequest(
            (new InternalRequest('https://acme.us/'))
                ->withPageId($sourcePageId)
                ->withInstructions([
                    (new TypoScriptInstruction(TemplateService::class))->withTypoScript([
                        'config.' => [
                            'recordLinks.' => [
                                'content.' => [
                                    'forceLink' => 1,
                                    'typolink.' => [
                                        'parameter' => 1200,
                                        'section.' => [
                                            'data' => 'field:uid',
                                            'wrap' => 'c|',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]),
                    $this->createTypoLinkInstruction([
                        'parameter' => $parameter,
                    ])
                ]),
            $this->internalRequestContext
        );

        static::assertSame($expectation, (string)$response->getBody());
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
}
