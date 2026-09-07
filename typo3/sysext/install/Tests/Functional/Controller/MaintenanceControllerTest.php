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

namespace TYPO3\CMS\Install\Tests\Functional\Controller;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\MaintenanceController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The database analyzer runs in the install tool, which is served from the
 * failsafe container. These tests therefore boot a failsafe container like
 * the install tool does and dispatch the controller actions from there.
 */
final class MaintenanceControllerTest extends FunctionalTestCase
{
    private const UNUSED_TABLE = 'tx_installtest_unused_table';

    protected array $coreExtensionsToLoad = ['install'];

    protected array $configurationToUseInTestInstance = [
        'BE' => [
            // Non-empty to satisfy the install tool integrity checks, value irrelevant.
            'installToolPassword' => '$argon2i$v=19$m=65536,t=16,p=1$placeholder',
        ],
    ];

    protected function tearDown(): void
    {
        // The schema survives between the tests of this case on non-sqlite platforms.
        $schemaManager = $this->getDefaultConnection()->createSchemaManager();
        foreach ([self::UNUSED_TABLE, 'zzz_deleted_' . self::UNUSED_TABLE] as $tableName) {
            if ($schemaManager->tablesExist([$tableName])) {
                $schemaManager->dropTable($tableName);
            }
        }
        parent::tearDown();
    }

    #[Test]
    public function databaseAnalyzerAnalyzeActionSuggestsRenamingUnusedTable(): void
    {
        $this->createUnusedTableWithOneRow();

        $response = $this->dispatchInstallToolAction('databaseAnalyzerAnalyzeAction');

        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($body['success']);
        $suggestions = array_column($body['suggestions'], null, 'key');
        self::assertArrayHasKey('renameTableToUnused', $suggestions);
        self::assertCount(1, $suggestions['renameTableToUnused']['children']);
        $child = $suggestions['renameTableToUnused']['children'][0];
        self::assertStringContainsString(self::UNUSED_TABLE, $child['statement']);
        self::assertStringContainsString('zzz_deleted_' . self::UNUSED_TABLE, $child['statement']);
        self::assertSame(1, $child['rowCount']);
    }

    #[Test]
    public function databaseAnalyzerExecuteActionRenamesUnusedTable(): void
    {
        $this->createUnusedTableWithOneRow();
        $analyzeResponse = $this->dispatchInstallToolAction('databaseAnalyzerAnalyzeAction');
        $analyzeBody = json_decode((string)$analyzeResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $suggestions = array_column($analyzeBody['suggestions'], null, 'key');
        $hash = $suggestions['renameTableToUnused']['children'][0]['hash'];

        $response = $this->dispatchInstallToolAction('databaseAnalyzerExecuteAction', ['hashes' => [$hash]]);

        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($body['success']);
        $titles = array_column($body['status'], 'title');
        self::assertSame(['Executed database updates'], $titles);
        $schemaManager = $this->getDefaultConnection()->createSchemaManager();
        self::assertFalse($schemaManager->tablesExist([self::UNUSED_TABLE]));
        self::assertTrue($schemaManager->tablesExist(['zzz_deleted_' . self::UNUSED_TABLE]));
    }

    private function createUnusedTableWithOneRow(): void
    {
        $connection = $this->getDefaultConnection();
        $table = new Table(self::UNUSED_TABLE);
        $table->addColumn('uid', Types::INTEGER);
        $connection->createSchemaManager()->createTable($table);
        $connection->insert(self::UNUSED_TABLE, ['uid' => 1]);
    }

    private function getDefaultConnection(): Connection
    {
        return $this->get(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
    }

    /**
     * Dispatch a MaintenanceController action the way the install tool does:
     * from the failsafe container. The action then boots the full container
     * itself via LateBootService. The container of the functional test is
     * restored afterwards.
     */
    private function dispatchInstallToolAction(string $action, array $installParameters = []): ResponseInterface
    {
        $request = (new ServerRequest(
            new Uri('http://localhost/typo3/install.php?install%5Bcontroller%5D=maintenance&install%5Bcontext%5D=install'),
            'POST',
            'php://input',
            [],
            [
                'SCRIPT_NAME' => '/typo3/install.php',
                'HTTP_HOST' => 'localhost',
                'SERVER_NAME' => 'localhost',
                'HTTPS' => 'off',
                'REMOTE_ADDR' => '127.0.0.1',
            ],
        ))
            ->withQueryParams(['install' => ['controller' => 'maintenance', 'context' => 'install']])
            ->withParsedBody(['install' => array_merge(['controller' => 'maintenance', 'context' => 'install'], $installParameters)]);

        $testContainer = GeneralUtility::getContainer();
        $testSingletonInstances = GeneralUtility::getSingletonInstances();
        $outputBufferingLevel = ob_get_level();
        try {
            $failsafeContainer = Bootstrap::init(ClassLoadingInformation::getClassLoader(), true);
            $controller = $failsafeContainer->get(MaintenanceController::class);
            return $controller->{$action}($request);
        } finally {
            // Drop output buffers opened by the bootstrap, but never phpunit's own one.
            while (ob_get_level() > $outputBufferingLevel) {
                ob_end_clean();
            }
            GeneralUtility::purgeInstances();
            GeneralUtility::setContainer($testContainer);
            GeneralUtility::resetSingletonInstances($testSingletonInstances);
            ExtensionManagementUtility::setPackageManager($testContainer->get(PackageManager::class));
        }
    }
}
