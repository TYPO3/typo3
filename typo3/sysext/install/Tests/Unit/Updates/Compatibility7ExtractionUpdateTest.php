<?php
declare(strict_types = 1);
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

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use TYPO3\CMS\Install\Updates\Compatibility7ExtractionUpdate;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class Compatibility7ExtractionUpdateTest
 */
class Compatibility7ExtractionUpdateTest extends UnitTestCase
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var SingletonInterface[]
     */
    protected $singletonInstances;

    protected function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
        $this->registry = $this->prophesize(Registry::class);
        GeneralUtility::setSingletonInstance(Registry::class, $this->registry->reveal());
    }

    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function checkForUpdateReturnsTrueIfWizardIsNotMarkedAsDoneYet()
    {
        $this->registry->get('installUpdate', Compatibility7ExtractionUpdate::class, false)->willReturn(false);
        $subject = new Compatibility7ExtractionUpdate();
        $description = '';
        $this->assertTrue($subject->checkForUpdate($description));
    }

    /**
     * @test
     */
    public function checkForUpdateReturnsFalseIfWizardIsMarkedAsDone()
    {
        $this->registry->get('installUpdate', Compatibility7ExtractionUpdate::class, false)->willReturn(true);
        $subject = new Compatibility7ExtractionUpdate();
        $description = '';
        $this->assertFalse($subject->checkForUpdate($description));
    }

    /**
     * @test
     */
    public function performUpdateReturnsFalseIfNoUserInputWasFound()
    {
        $_GET['install'] = [];
        $subject = new Compatibility7ExtractionUpdate();
        $databaseQueries = [];
        $customMessage = '';
        $this->assertFalse($subject->performUpdate($databaseQueries, $customMessage));
    }

    /**
     * @test
     */
    public function performUpdateReturnsTrueIfUserDeclinesInstallAndMarksWizardDone()
    {
        $_GET['install']['values']['compatibility7Extension']['install'] = 0;
        $subject = new Compatibility7ExtractionUpdate();
        $databaseQueries = [];
        $customMessage = '';
        $this->assertTrue($subject->performUpdate($databaseQueries, $customMessage));
        $this->registry->set('installUpdate', Compatibility7ExtractionUpdate::class, 1)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function performUpdateInstallsExtensionUponRequestAndMarksWizardDone()
    {
        $_GET['install']['values']['compatibility7Extension']['install'] = 1;

        $objectManager = $this->prophesize(ObjectManager::class);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager->reveal());

        $listUtility = $this->prophesize(ListUtility::class);
        $installUtility = $this->prophesize(InstallUtility::class);
        $objectManager->get(InstallUtility::class)->willReturn($installUtility->reveal());
        $objectManager->get(ListUtility::class)->willReturn($listUtility->reveal());
        $extensionList = ['compatibility7' => []];
        $listUtility->getAvailableExtensions()->willReturn($extensionList);
        $listUtility->getAvailableAndInstalledExtensions($extensionList)->willReturn($extensionList);
        $installUtility->install('compatibility7')->shouldBeCalled();

        $subject = new Compatibility7ExtractionUpdate();
        $databaseQueries = [];
        $customMessage = '';
        $this->assertTrue($subject->performUpdate($databaseQueries, $customMessage));
        $this->registry->set('installUpdate', Compatibility7ExtractionUpdate::class, 1)->shouldHaveBeenCalled();
    }
}
