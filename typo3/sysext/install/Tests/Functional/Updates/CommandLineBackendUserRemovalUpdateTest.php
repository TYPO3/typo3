<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Tests\Functional\Updates;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\CommandLineBackendUserRemovalUpdate;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class CommandLineBackendUserRemovalUpdateTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function cliUsersAreMarkedAsDeleted()
    {
        $this->importCSVDataSet(GeneralUtility::getFileAbsFileName(
            'typo3/sysext/install/Tests/Functional/Updates/DataSet/CommandLineBackendUserRemovalBefore.csv'
        ));
        $databaseQueries = [];
        $customMessage = '';
        (new CommandLineBackendUserRemovalUpdate())->performUpdate($databaseQueries, $customMessage);
        $this->assertCSVDataSet(GeneralUtility::getFileAbsFileName(
            'typo3/sysext/install/Tests/Functional/Updates/DataSet/CommandLineBackendUserRemovalAfter.csv'
        ));
    }
}
