<?php
namespace TYPO3\CMS\Backend\Tests\Functional\Controller\Page;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * Test case for TYPO3\CMS\Backend\Controller\Page\LocalizationController
 */
class BackendUtilityTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    /**
     * Sets up this test case.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Utility/Fixtures/sys_domain.xml');
    }

    /**
     * @test
     */
    public function determineFirstDomainRecord()
    {
        $rootLineUtility = GeneralUtility::makeInstance(RootlineUtility::class, 4);
        $rootLine = $rootLineUtility->get();
        $this->assertEquals('example.com', BackendUtility::firstDomainRecord($rootLine));
    }
}
