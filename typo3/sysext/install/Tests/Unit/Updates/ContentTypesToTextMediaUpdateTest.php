<?php
namespace TYPO3\CMS\Install\Tests\Unit\Updates;

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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Tests\Unit\Resource\BaseTestCase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Install\Updates\ContentTypesToTextMediaUpdate as UpdateWizard;

/**
 * Test Class for ContentTypesToTextMediaUpdate
 */
class ContentTypesToTextMediaUpdateTest extends BaseTestCase
{
    /**
     * @var ObjectProphecy
     */
    protected $packageManagerProphecy;

    /**
     * @var ObjectProphecy
     */
    protected $dbProphecy;

    /**
     * @var ObjectProphecy
     */
    protected $updateWizard;

    public function setUp()
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']);
        $prophet = new Prophet();
        $this->packageManagerProphecy = $prophet->prophesize(PackageManager::class);
        $this->dbProphecy = $prophet->prophesize(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $this->dbProphecy->reveal();
        $this->updateWizard = new UpdateWizard();
        ExtensionManagementUtility::setPackageManager($this->packageManagerProphecy->reveal());
    }

    public function tearDown()
    {
        ExtensionManagementUtility::setPackageManager(new PackageManager());
    }

    /**
     * @test
     * @return void
     */
    public function updateWizardDoesNotRunIfCssStyledContentIsInstalled()
    {
        $this->packageManagerProphecy->isPackageActive('fluid_styled_content')->willReturn(true);
        $this->packageManagerProphecy->isPackageActive('css_styled_content')->willReturn(true);

        $description = '';
        $this->assertFalse($this->updateWizard->checkForUpdate($description));
    }

    /**
     * @test
     * @return void
     */
    public function updateWizardDoesRunIfCssStyledContentIsNotInstalledAndDataToUpdate()
    {
        $this->packageManagerProphecy->isPackageActive('fluid_styled_content')->willReturn(true);
        $this->packageManagerProphecy->isPackageActive('css_styled_content')->willReturn(false);
        $this->dbProphecy->exec_SELECTcountRows(Argument::cetera())->willReturn(1);

        $description = '';
        $this->assertTrue($this->updateWizard->checkForUpdate($description));
    }
}
