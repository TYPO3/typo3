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

use Codeception\Events;
use Codeception\Extension;
use TYPO3\TestingFramework\Core\Testbase;

/**
 * This codeception extension creates a basic TYPO3 instance within
 * typo3temp. It is used as a basic acceptance test that clicks through
 * the TYPO3 installation steps.
 *
 * @internal Used by core, do not use in extensions, may vanish later.
 */
final class InstallSqliteCoreEnvironment extends Extension
{
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
    public function bootstrapTypo3Environment()
    {
        $testbase = new Testbase();
        $testbase->defineOriginalRootPath();

        $instancePath = ORIGINAL_ROOT . 'typo3temp/var/tests/acceptance';
        $testbase->removeOldInstanceIfExists($instancePath);
        putenv('TYPO3_PATH_ROOT=' . $instancePath);
        putenv('TYPO3_PATH_APP=' . $instancePath);
        $testbase->setTypo3TestingContext();

        $testbase->createDirectory($instancePath);
        $testbase->setUpInstanceCoreLinks($instancePath);
        touch($instancePath . '/FIRST_INSTALL');
    }
}
