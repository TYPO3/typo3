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
