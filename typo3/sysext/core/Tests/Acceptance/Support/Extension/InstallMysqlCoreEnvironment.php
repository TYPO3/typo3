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
class InstallMysqlCoreEnvironment extends Extension
{
    /**
     * @var array Default configuration values
     */
    protected $config = [
        'typo3InstallMysqlDatabaseHost' => '127.0.0.1',
        'typo3InstallMysqlDatabasePassword' => '',
        'typo3InstallMysqlDatabaseUsername' => 'root',
        'typo3InstallMysqlDatabaseName' => 'core_install',
    ];

    /**
     * Override configuration from ENV if needed
     */
    public function _initialize()
    {
        $env = getenv('typo3InstallMysqlDatabaseHost');
        $this->config['typo3InstallMysqlDatabaseHost'] = is_string($env)
            ? trim($env)
            : trim($this->config['typo3InstallMysqlDatabaseHost']);

        $env = getenv('typo3InstallMysqlDatabasePassword');
        $this->config['typo3InstallMysqlDatabasePassword'] = is_string($env)
            ? trim($env)
            : trim($this->config['typo3InstallMysqlDatabasePassword']);

        $env = getenv('typo3InstallMysqlDatabaseUsername');
        $this->config['typo3InstallMysqlDatabaseUsername'] = is_string($env)
            ? trim($env)
            : $this->config['typo3InstallMysqlDatabaseUsername'];

        $env = getenv('typo3InstallMysqlDatabaseName');
        $this->config['typo3InstallMysqlDatabaseName'] = (is_string($env) && !empty($env))
            ? mb_strtolower(trim($env))
            : mb_strtolower(trim($this->config['typo3InstallMysqlDatabaseName']));

        if (empty($this->config['typo3InstallMysqlDatabaseName'])) {
            throw new \RuntimeException('No database name given', 1530827194);
        }
    }

    /**
     * Events to listen to
     */
    public static $events = [
        Events::TEST_BEFORE => 'bootstrapTypo3Environment',
    ];

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
            'driver' => 'mysqli',
            'host' => $this->config['typo3InstallMysqlDatabaseHost'],
            'port' => 3306,
            'password' => $this->config['typo3InstallMysqlDatabasePassword'],
            'user' => $this->config['typo3InstallMysqlDatabaseUsername'],
        ];
        $this->output->debug('Connecting to MySQL: ' . json_encode($connectionParameters));
        $databaseName = $this->config['typo3InstallMysqlDatabaseName'];
        $schemaManager = DriverManager::getConnection($connectionParameters)->createSchemaManager();
        $this->output->debug("Database: $databaseName");
        if (in_array($databaseName, $schemaManager->listDatabases(), true)) {
            $this->output->debug("Dropping database $databaseName");
            $schemaManager->dropDatabase($databaseName);
        }

        $testbase->createDirectory($instancePath);
        $testbase->setUpInstanceCoreLinks($instancePath);
        touch($instancePath . '/FIRST_INSTALL');

        // Have config available in test
        $event->getTest()->getMetadata()->setCurrent($this->config);
    }
}
