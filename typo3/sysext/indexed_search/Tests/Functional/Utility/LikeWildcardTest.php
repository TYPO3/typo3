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

namespace TYPO3\CMS\IndexedSearch\Tests\Functional\Utility;

use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\IndexedSearch\Utility\LikeWildcard;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LikeWildcardTest extends FunctionalTestCase
{
    #[DataProvider('getLikeQueryPartDataProvider')]
    #[Test]
    public function getLikeQueryPart(string $tableName, string $fieldName, string $likeValue, LikeWildcard $subject, array $expected): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable($tableName);
        $databasePlatform = $connection->getDatabasePlatform();
        $expectedToUse = null;
        foreach ($expected as $expectedPlatform => $expectedForPlatform) {
            if ($databasePlatform instanceof $expectedPlatform) {
                $expectedToUse = $expectedForPlatform;
            }
        }
        self::assertSame($expectedToUse, $subject->getLikeQueryPart($tableName, $fieldName, $likeValue));
    }

    /**
     * Returns data sets for the test getLikeQueryPart
     * Each dataset is an array with the following elements:
     * - the table name
     * - the field name
     * - the search value
     * - the wildcard mode
     * - the expected result
     */
    public static function getLikeQueryPartDataProvider(): array
    {
        return [
            'no placeholders and no wildcard mode' => [
                'tableName' => 'tt_content',
                'fieldName' => 'body',
                'likeValue' => 'searchstring',
                'subject' => LikeWildcard::NONE,
                'expected' => [
                    DoctrinePostgreSQLPlatform::class => '(("body")::text) ILIKE \'searchstring\'',
                    DoctrineMariaDBPlatform::class => '`body` LIKE \'searchstring\' ESCAPE \'\\\\\'',
                    DoctrineMySQLPlatform::class => '`body` LIKE \'searchstring\' ESCAPE \'\\\\\'',
                    DoctrineSQLitePlatform::class => '"body" LIKE \'searchstring\' ESCAPE \'\\\'',
                ],
            ],
            'no placeholders and left wildcard mode' => [
                'tableName' => 'tt_content',
                'fieldName' => 'body',
                'likeValue' => 'searchstring',
                'subject' => LikeWildcard::LEFT,
                'expected' => [
                    DoctrinePostgreSQLPlatform::class => '(("body")::text) ILIKE \'%searchstring\'',
                    DoctrineMariaDBPlatform::class => '`body` LIKE \'%searchstring\' ESCAPE \'\\\\\'',
                    DoctrineMySQLPlatform::class => '`body` LIKE \'%searchstring\' ESCAPE \'\\\\\'',
                    DoctrineSQLitePlatform::class => '"body" LIKE \'%searchstring\' ESCAPE \'\\\'',
                ],
            ],
            'no placeholders and right wildcard mode' => [
                'tableName' => 'tt_content',
                'fieldName' => 'body',
                'likeValue' => 'searchstring',
                'subject' => LikeWildcard::RIGHT,
                'expected' => [
                    DoctrinePostgreSQLPlatform::class => '(("body")::text) ILIKE \'searchstring%\'',
                    DoctrineMariaDBPlatform::class => '`body` LIKE \'searchstring%\' ESCAPE \'\\\\\'',
                    DoctrineMySQLPlatform::class => '`body` LIKE \'searchstring%\' ESCAPE \'\\\\\'',
                    DoctrineSQLitePlatform::class => '"body" LIKE \'searchstring%\' ESCAPE \'\\\'',
                ],
            ],
            'no placeholders and both wildcards mode' => [
                'tableName' => 'tt_content',
                'fieldName' => 'body',
                'likeValue' => 'searchstring',
                'subject' => LikeWildcard::BOTH,
                'expected' => [
                    DoctrinePostgreSQLPlatform::class => '(("body")::text) ILIKE \'%searchstring%\'',
                    DoctrineMariaDBPlatform::class => '`body` LIKE \'%searchstring%\' ESCAPE \'\\\\\'',
                    DoctrineMySQLPlatform::class => '`body` LIKE \'%searchstring%\' ESCAPE \'\\\\\'',
                    DoctrineSQLitePlatform::class => '"body" LIKE \'%searchstring%\' ESCAPE \'\\\'',
                ],
            ],
            'underscore placeholder and left wildcard mode' => [
                'tableName' => 'tt_content',
                'fieldName' => 'body',
                'likeValue' => 'search_string',
                'subject' => LikeWildcard::LEFT,
                'expected' => [
                    DoctrinePostgreSQLPlatform::class => '(("body")::text) ILIKE \'%search\_string\'',
                    DoctrineMariaDBPlatform::class => '`body` LIKE \'%search\\\\_string\' ESCAPE \'\\\\\'',
                    DoctrineMySQLPlatform::class => '`body` LIKE \'%search\\\\_string\' ESCAPE \'\\\\\'',
                    DoctrineSQLitePlatform::class => '"body" LIKE \'%search\_string\' ESCAPE \'\\\'',
                ],
            ],
            'percent placeholder and right wildcard mode' => [
                'tableName' => 'tt_content',
                'fieldName' => 'body',
                'likeValue' => 'search%string',
                'subject' => LikeWildcard::RIGHT,
                'expected' => [
                    DoctrinePostgreSQLPlatform::class => '(("body")::text) ILIKE \'search\%string%\'',
                    DoctrineMariaDBPlatform::class => '`body` LIKE \'search\\\\%string%\' ESCAPE \'\\\\\'',
                    DoctrineMySQLPlatform::class => '`body` LIKE \'search\\\\%string%\' ESCAPE \'\\\\\'',
                    DoctrineSQLitePlatform::class => '"body" LIKE \'search\%string%\' ESCAPE \'\\\'',
                ],
            ],
            'percent and underscore placeholder and both wildcards mode' => [
                'tableName' => 'tt_content',
                'fieldName' => 'body',
                'likeValue' => '_search%string_',
                'subject' => LikeWildcard::RIGHT,
                'expected' => [
                    DoctrinePostgreSQLPlatform::class => '(("body")::text) ILIKE \'\_search\%string\_%\'',
                    DoctrineMariaDBPlatform::class => '`body` LIKE \'\\\\_search\\\\%string\\\\_%\' ESCAPE \'\\\\\'',
                    DoctrineMySQLPlatform::class => '`body` LIKE \'\\\\_search\\\\%string\\\\_%\' ESCAPE \'\\\\\'',
                    DoctrineSQLitePlatform::class => '"body" LIKE \'\_search\%string\_%\' ESCAPE \'\\\'',
                ],
            ],
        ];
    }
}
