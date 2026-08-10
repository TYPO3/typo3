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

namespace TYPO3\CMS\Extensionmanager\Tests\Functional\Utility;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ListUtilityTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'extensionmanager',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extensionmanager/Tests/Functional/Fixtures/Extensions/deepl_base',
        'typo3/sysext/extensionmanager/Tests/Functional/Fixtures/Extensions/deepl_write',
        'typo3/sysext/extensionmanager/Tests/Functional/Fixtures/Extensions/deepltranslate_core',
    ];

    #[Test]
    public function getAvailableAndInstalledExtensionsReturnsExpectedResultForComplexExtensionDependencyChainConstellation(): void
    {
        // Populate `tx_extensionmanager_domain_model_extension` TER extension information for
        // test fixture extensions **not** really available in TER. This is **mandatory** to
        // provide the complex constellation, which listed the extensions with a shared base
        // extension required in conflicting major versions. That previously aborted the whole
        // listing with:
        //
        //   TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException:
        //   deepl_base was requested to be downloaded in different versions (2.0.2 and 1.0.3).
        $this->addTerInformation($this->deeplBasedComplexMultiExtensionDependencyChainConstellation());

        $listUtility = $this->get(ListUtility::class);
        $invoker = new \ReflectionMethod($listUtility, 'getAvailableAndInstalledExtensionsWithAdditionalInformation');
        $result = $invoker->invoke($listUtility);

        self::assertArrayHasKey('deepl_base', $result, 'deepl_base information exists in result');
        self::assertArrayHasKey('deepl_write', $result, 'deepl_write information exists in result');
        self::assertArrayHasKey('deepltranslate_core', $result, 'deepltranslate_core information exists in result');

        // deepl_base is available in a higher major version supporting the current core version.
        self::assertTrue($result['deepl_base']['updateAvailable']);
        self::assertSame('2.0.2', $result['deepl_base']['updateToVersion']->version);

        // deepl_write has no next major version, so it stays on the highest release of its own
        // major version, which requires the previous major version of deepl_base.
        self::assertTrue($result['deepl_write']['updateAvailable']);
        self::assertSame('1.0.2', $result['deepl_write']['updateToVersion']->version);

        // deepltranslate_core has a next major version requiring the next major version of
        // deepl_base, although a later release of its previous major version exists.
        self::assertTrue($result['deepltranslate_core']['updateAvailable']);
        self::assertSame('2.0.1', $result['deepltranslate_core']['updateToVersion']->version);

        // Determining update candidates is a query and must not leave extensions queued for
        // download, update or installation.
        $downloadQueue = $this->get(DownloadQueue::class);
        self::assertSame([], $downloadQueue->getExtensionQueue());
        self::assertSame([], $downloadQueue->getExtensionInstallStorage());
    }

    private function addTerInformation(array $data): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('tx_extensionmanager_domain_model_extension');
        foreach ($data as $entry) {
            $connection->insert('tx_extensionmanager_domain_model_extension', $entry);
        }
    }

    private function deeplBasedComplexMultiExtensionDependencyChainConstellation(): array
    {
        $currentMajor = new Typo3Version()->getMajorVersion();
        $nextMajor = $currentMajor + 1;
        $previousMajor = $currentMajor - 1;
        $base = [
            'pid' => 0,
            'alldownloadcounter' => 0,
            'downloadcounter' => 0,
            'state' => 2,
            'review_state' => 0,
            'category' => 4,
            'last_updated' => 1749574392,
            'author_name' => 'TYPO3 Core Team',
            'author_email' => 'not-existing-user@example.tld',
            'ownerusername' => 'some-user',
            'md5hash' => 'ad0fe0f35f3c11ac9daf99d0ebe50560',
            'update_comment' => '',
            'authorcompany' => 'TYPO3.org',
            'current_version' => 0,
            'lastreviewedversion' => 0,
            'documentation_link' => '',
            'distribution_image' => '',
        ];
        $baseDeeplBase = [
            'extension_key' => 'deepl_base',
            'remote' => 'ter',
            'title' => 'Extension (Base)',
            'description' => 'Demonstrate a base extension required by another extension',
        ];
        $baseDeeplWrite = [
            'extension_key' => 'deepl_write',
            'remote' => 'ter',
            'title' => 'Extension (Second)',
            'description' => 'Demonstrate extension requiring Extension (Base)',
        ];
        $baseDeepltranslateCore = [
            'extension_key' => 'deepltranslate_core',
            'remote' => 'ter',
            'title' => 'Extension (Third)',
            'description' => 'Demonstrate extension requiring Extension (Base)',
        ];
        return [
            // deepl_base - current version installed
            array_replace($base, $baseDeeplBase, [
                'version' => '1.0.0',
                'serialized_dependencies' => serialize([
                    'depends' => [
                        'typo3' => sprintf('%s.0.0-%s.99.99', $previousMajor, $currentMajor),
                    ],
                ]),
                'integer_version' => 1000000,
            ]),
            // deepl_base - additional versions same extension major version
            array_replace($base, $baseDeeplBase, [
                'version' => '1.0.1',
                'serialized_dependencies' => serialize([
                    'depends' => [
                        'typo3' => sprintf('%s.0.0-%s.99.99', $previousMajor, $currentMajor),
                    ],
                ]),
                'integer_version' => 1000001,
            ]),
            array_replace($base, $baseDeeplBase, [
                'version' => '1.0.2',
                'serialized_dependencies' => serialize([
                    'depends' => [
                        'typo3' => sprintf('%s.0.0-%s.99.99', $previousMajor, $currentMajor),
                    ],
                ]),
                'integer_version' => 1000002,
            ]),
            // deepl_base - additional versions next extension major version suitable for same core version
            array_replace($base, $baseDeeplBase, [
                'version' => '2.0.0',
                'serialized_dependencies' => serialize([
                    'depends' => [
                        'typo3' => sprintf('%s.0.0-%s.99.99', $currentMajor, $nextMajor),
                    ],
                ]),
                'integer_version' => 2000000,
            ]),
            array_replace($base, $baseDeeplBase, [
                'version' => '2.0.1',
                'serialized_dependencies' => serialize([
                    'depends' => [
                        'typo3' => sprintf('%s.0.0-%s.99.99', $currentMajor, $nextMajor),
                    ],
                ]),
                'integer_version' => 2000001,
            ]),
            array_replace($base, $baseDeeplBase, [
                'version' => '2.0.2',
                'serialized_dependencies' => serialize([
                    'depends' => [
                        'typo3' => sprintf('%s.0.0-%s.99.99', $currentMajor, $nextMajor),
                    ],
                ]),
                'integer_version' => 2000002,
            ]),
            // deepl_base - current major version release **after** next extension major version release
            array_replace($base, $baseDeeplBase, [
                'version' => '1.0.3',
                'serialized_dependencies' => serialize([
                    'depends' => [
                        'typo3' => sprintf('%s.0.0-%s.99.99', $previousMajor, $currentMajor),
                    ],
                ]),
                'integer_version' => 1000003,
            ]),
            // deepl_write - current version installed
            array_replace($base, $baseDeeplWrite, [
                'version' => '1.0.0',
                'serialized_dependencies' => serialize([
                    'depends' => [
                        'typo3' => sprintf('%s.0.0-%s.99.99', $previousMajor, $currentMajor),
                        'deepl_base' => '1.0.0-1.99.99',
                    ],
                    'suggests' => [
                        'deepltranslate_core' => '',
                    ],
                ]),
                'integer_version' => 1000000,
            ]),
            // deepl_write - additional versions same extension major version
            array_replace($base, $baseDeeplWrite, [
                'version' => '1.0.1',
                'serialized_dependencies' => serialize([
                    'depends' => [
                        'typo3' => sprintf('%s.0.0-%s.99.99', $previousMajor, $currentMajor),
                        'deepl_base' => '1.0.1-1.99.99',
                    ],
                    'suggests' => [
                        'deepltranslate_core' => '',
                    ],
                ]),
                'integer_version' => 1000001,
            ]),
            array_replace($base, $baseDeeplWrite, [
                'version' => '1.0.2',
                'serialized_dependencies' => serialize([
                    'depends' => [
                        'typo3' => sprintf('%s.0.0-%s.99.99', $previousMajor, $currentMajor),
                        'deepl_base' => '1.0.2-1.99.99',
                    ],
                    'suggests' => [
                        'deepltranslate_core' => '',
                    ],
                ]),
                'integer_version' => 1000002,
            ]),
            // NOTE: deepl_write - no next major version with shifted core version support is intentionally
            // deepltranslate_core - current version installed
            array_replace($base, $baseDeepltranslateCore, [
                'version' => '1.0.0',
                'serialized_dependencies' => serialize([
                    'depends' => [
                        'typo3' => sprintf('%s.0.0-%s.99.99', $previousMajor, $currentMajor),
                        'deepl_base' => '1.0.0-1.99.99',
                    ],
                ]),
                'integer_version' => 1000000,
            ]),
            // deepltranslate_core - additional versions same extension major version
            array_replace($base, $baseDeepltranslateCore, [
                'version' => '1.0.1',
                'serialized_dependencies' => serialize([
                    'depends' => [
                        'typo3' => sprintf('%s.0.0-%s.99.99', $previousMajor, $currentMajor),
                        'deepl_base' => '1.0.1.99.99',
                    ],
                ]),
                'integer_version' => 1000001,
            ]),
            array_replace($base, $baseDeepltranslateCore, [
                'version' => '1.0.2',
                'serialized_dependencies' => serialize([
                    'depends' => [
                        'typo3' => sprintf('%s.0.0-%s.99.99', $previousMajor, $currentMajor),
                        'deepl_base' => '1.0.1-1.99.99',
                    ],
                ]),
                'integer_version' => 1000002,
            ]),
            // deepltranslate_core - additional versions next extension major version suitable for same core version
            array_replace($base, $baseDeepltranslateCore, [
                'version' => '2.0.0',
                'serialized_dependencies' => serialize([
                    'depends' => [
                        'typo3' => sprintf('%s.0.0-%s.99.99', $currentMajor, $nextMajor),
                        'deepl_base' => '2.0.0-2.99.99',
                    ],
                ]),
                'integer_version' => 2000000,
            ]),
            array_replace($base, $baseDeepltranslateCore, [
                'version' => '2.0.1',
                'serialized_dependencies' => serialize([
                    'depends' => [
                        'typo3' => sprintf('%s.0.0-%s.99.99', $currentMajor, $nextMajor),
                        'deepl_base' => '2.0.1-2.99.99',
                    ],
                ]),
                'integer_version' => 2000001,
            ]),
            // ---
            array_replace($base, $baseDeepltranslateCore, [
                'version' => '1.0.3',
                'serialized_dependencies' => serialize([
                    'depends' => [
                        'typo3' => sprintf('%s.0.0-%s.99.99', $previousMajor, $currentMajor),
                        'deepl_base' => '1.0.1-1.99.99',
                    ],
                ]),
                'integer_version' => 1000003,
            ]),
        ];
    }
}
