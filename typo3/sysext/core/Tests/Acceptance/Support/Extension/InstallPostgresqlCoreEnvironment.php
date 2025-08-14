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

namespace TYPO3\CMS\Core\Tests\Acceptance\Support\Extension;

use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use Doctrine\DBAL\DriverManager;
use TYPO3\TestingFramework\Core\Testbase;

/**
 * This codeception extension creates a basic TYPO3 instance within
 * typo3temp. It is used as a basic acceptance test that clicks through
 * the TYPO3 installation steps.
 *
 * @internal Used by core, do not use in extensions, may vanish later.
 */
final class InstallPostgresqlCoreEnvironment extends Extension
{
    /**
     * @var array Default configuration values
     */
    protected array $config = [
        'typo3InstallPostgresqlDatabaseHost' => '127.0.0.1',
        'typo3InstallPostgresqlDatabasePort' => 5432,
        'typo3InstallPostgresqlDatabasePassword' => '',
        'typo3InstallPostgresqlDatabaseUsername' => '',
        'typo3InstallPostgresqlDatabaseName' => 'core_install',
    ];

    /**
     * Events to listen to
     */
    public static $events = [
        Events::TEST_BEFORE => 'bootstrapTypo3Environment',
    ];

    /**
     * Override configuration from ENV if needed
     */
    public function _initialize(): void
    {
        $env = getenv('typo3InstallPostgresqlDatabaseHost');
        $this->config['typo3InstallPostgresqlDatabaseHost'] = is_string($env)
            ? trim($env)
            : trim($this->config['typo3InstallPostgresqlDatabaseHost']);

        $env = getenv('typo3InstallPostgresqlDatabasePort');
        $this->config['typo3InstallPostgresqlDatabasePort'] = is_string($env)
            ? (int)$env
            : (int)$this->config['typo3InstallPostgresqlDatabasePort'];

        $env = getenv('typo3InstallPostgresqlDatabasePassword');
        $this->config['typo3InstallPostgresqlDatabasePassword'] = is_string($env)
            ? trim($env)
            : trim($this->config['typo3InstallPostgresqlDatabasePassword']);

        $env = getenv('typo3InstallPostgresqlDatabaseUsername');
        $this->config['typo3InstallPostgresqlDatabaseUsername'] = is_string($env)
            ? trim($env)
            : $this->config['typo3InstallPostgresqlDatabaseUsername'];

        $env = getenv('typo3InstallPostgresqlDatabaseName');
        $this->config['typo3InstallPostgresqlDatabaseName'] = (is_string($env) && !empty($env))
            ? mb_strtolower(trim($env))
            : mb_strtolower(trim($this->config['typo3InstallPostgresqlDatabaseName']));

        if (empty($this->config['typo3InstallPostgresqlDatabaseName'])) {
            throw new \RuntimeException('No database name given', 1530827195);
        }
    }

    /**
     * Handle SUITE_BEFORE event.
     *
     * Create a full standalone TYPO3 instance within typo3temp/var/tests/acceptance,
     * create a database and create database schema.
     */
    public function bootstrapTypo3Environment(TestEvent $event)
    {
        $testbase = new Testbase();
        $testbase->defineOriginalRootPath();

        $instancePath = ORIGINAL_ROOT . 'typo3temp/var/tests/acceptance';
        $testbase->removeOldInstanceIfExists($instancePath);
        putenv('TYPO3_PATH_ROOT=' . $instancePath);
        putenv('TYPO3_PATH_APP=' . $instancePath);
        $testbase->setTypo3TestingContext();

        // Drop db from a previous run if exists
        $connectionParameters = [
            'driver' => 'pdo_pgsql',
            'host' => $this->config['typo3InstallPostgresqlDatabaseHost'],
            'port' => $this->config['typo3InstallPostgresqlDatabasePort'],
            'password' => $this->config['typo3InstallPostgresqlDatabasePassword'],
            'user' => $this->config['typo3InstallPostgresqlDatabaseUsername'],
        ];
        $this->output->debug('Connecting to PgSQL: ' . json_encode($connectionParameters));
        $schemaManager = DriverManager::getConnection($connectionParameters)->createSchemaManager();
        $databaseName = $this->config['typo3InstallPostgresqlDatabaseName'];
        $this->output->debug("Database: $databaseName");
        if (in_array($databaseName, $schemaManager->listDatabases(), true)) {
            $this->output->debug("Dropping database $databaseName");
            $schemaManager->dropDatabase($databaseName);
        }
        $schemaManager->createDatabase($databaseName);

        $testbase->createDirectory($instancePath);
        $testbase->setUpInstanceCoreLinks($instancePath);
        touch($instancePath . '/FIRST_INSTALL');

        // Have config available in test
        $event->getTest()->getMetadata()->setCurrent($this->config);
    }
}
