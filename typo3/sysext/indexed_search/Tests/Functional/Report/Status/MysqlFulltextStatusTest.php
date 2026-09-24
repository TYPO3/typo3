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

namespace TYPO3\CMS\IndexedSearch\Tests\Functional\Report\Status;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\IndexedSearch\Report\Status\MysqlFulltextStatus;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MysqlFulltextStatusTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['indexed_search', 'reports'];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'indexed_search' => [
                'useMysqlFulltext' => '1',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function statusReportsMinimumWordLengthOfTheDatabaseServer(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('index_fulltext');
        if (!$connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            self::markTestSkipped('MySQL fulltext is only available on MySQL and MariaDB');
        }
        $variables = $connection->executeQuery(
            "SHOW VARIABLES WHERE Variable_name IN ('ft_min_word_len', 'innodb_ft_min_token_size')"
        )->fetchAllKeyValue();
        $engine = $connection->executeQuery(
            'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            ['index_fulltext']
        )->fetchOne();
        $expectedLength = strtolower((string)$engine) === 'myisam'
            ? $variables['ft_min_word_len']
            : $variables['innodb_ft_min_token_size'];

        $statuses = $this->get(MysqlFulltextStatus::class)->getStatus();

        self::assertArrayHasKey('minimumWordLength', $statuses);
        self::assertSame(ContextualFeedbackSeverity::INFO, $statuses['minimumWordLength']->getSeverity());
        self::assertSame($expectedLength . ' characters', $statuses['minimumWordLength']->getValue());
        self::assertStringContainsString('shorter than ' . $expectedLength . ' characters', $statuses['minimumWordLength']->getMessage());
    }
}
