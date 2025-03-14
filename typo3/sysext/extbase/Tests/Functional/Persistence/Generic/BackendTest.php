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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence\Generic;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Backend;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\DateExample;

final class BackendTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
    }

    public static function getPlainValueMapsDateTimeDataProvider(): \Generator
    {
        $cases = [
            'persists null for nullable native DateTime' => [
                'property' => 'datetimeDatetime',
                'model' => DateExample::class,
                'field' => 'datetime_datetime',
                'table' => 'tx_blogexample_domain_model_dateexample',
                'value' => null,
                'expected' => null,
            ],
            'persists null for nullable integer DateTime' => [
                'property' => 'datetimeInt',
                'model' => DateExample::class,
                'field' => 'datetime_int',
                'table' => 'tx_blogexample_domain_model_dateexample',
                'value' => null,
                'expected' => null,
            ],
            'persists DateTime for native DateTime' => [
                'property' => 'datetimeDatetime',
                'model' => DateExample::class,
                'field' => 'datetime_datetime',
                'table' => 'tx_blogexample_domain_model_dateexample',
                'value' => new \DateTime('2025-01-21T15:10:03Z'),
                'expected' => '2025-01-21 15:10:03',
            ],
            'persists DateTime for integer DateTime' => [
                'property' => 'datetimeInt',
                'model' => DateExample::class,
                'field' => 'datetime_int',
                'table' => 'tx_blogexample_domain_model_dateexample',
                'value' => new \DateTime('2025-01-21T15:10:03Z'),
                // date --date=2025-01-21T15:10:03Z +%s
                'expected' => 1737472203,
            ],
            'persists DateTime with non localtime offset for native DateTime' => [
                'property' => 'datetimeDatetime',
                'model' => DateExample::class,
                'field' => 'datetime_datetime',
                'table' => 'tx_blogexample_domain_model_dateexample',
                'value' => new \DateTime('2025-01-21T15:10:03+03:00'),
                'expected' => '2025-01-21 12:10:03',
                // legacy differs!
                'expectedLegacy' => '2025-01-21 15:10:03',
            ],
            'persists DateTime with non localtime for integer DateTime' => [
                'property' => 'datetimeInt',
                'model' => DateExample::class,
                'field' => 'datetime_int',
                'table' => 'tx_blogexample_domain_model_dateexample',
                'value' => new \DateTime('2025-01-21T15:10:03+03:00'),
                // date --date=2025-01-21T15:10:03+03:00 +%s
                'expected' => 1737461403,
            ],
        ];

        foreach ($cases as $description => $data) {
            $expected = $data['expected'];
            $expectedLegacy = array_key_exists('expectedLegacy', $data) ? $data['expectedLegacy'] : $expected;
            unset($data['expected'], $data['expectedLegacy']);

            $modes = [
                [
                    'consistentDateTimeHandling' => true,
                    'expected' => $expected,
                ],
                [
                    'consistentDateTimeHandling' => false,
                    'expected' => $expectedLegacy,
                ],
            ];

            foreach ($modes as $mode) {
                $suffix = ' (consistentDateTimeHandling=' . ($mode['consistentDateTimeHandling'] ? 'true' : 'false') . ')';
                yield $description . $suffix => [...$data, ...$mode];
            }
        }
    }

    #[DataProvider('getPlainValueMapsDateTimeDataProvider')]
    #[Test]
    public function getPlainValueMapsDateTime(
        string $property,
        string $model,
        string $field,
        string $table,
        ?\DateTimeInterface $value,
        bool $consistentDateTimeHandling,
        null|string|int $expected,
    ): void {
        $bak = $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.consistentDateTimeHandling'];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.consistentDateTimeHandling'] = $consistentDateTimeHandling;

        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendTest/getPlainValueMapsDateTimeImport.csv');
        $date = $this->get(PersistenceManager::class)->getObjectByIdentifier(1, $model);
        $date->{'set' . ucfirst($property)}($value);
        $changedEntities = new ObjectStorage();
        $changedEntities->attach($date);

        $backend = $this->get(Backend::class);
        $backend->setChangedEntities($changedEntities);
        $backend->commit();

        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->select($field)
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();
        self::assertNotFalse($row);
        self::assertEquals($expected, $row[$field]);

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.consistentDateTimeHandling'] = $bak;
    }
}
