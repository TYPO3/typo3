<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Controller;

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

use TYPO3\CMS\Components\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\CMS\Extensionmanager\Controller\UpdateFromTerController;

/**
 * Update from TER controller test
 *
 */
class UpdateFromTerControllerTest extends \TYPO3\CMS\Components\TestingFramework\Core\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository
     */
    protected $repositoryRepositoryMock;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
     */
    protected $extensionRepositoryMock;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper
     */
    protected $repositoryHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Lang\LanguageService
     */
    protected $languageServiceMock;

    protected function setUp()
    {
        $this->mockObjectManager = $this->getMockBuilder(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class)->getMock();
        $this->repositoryRepositoryMock = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository::class)
            ->setMethods(['findByUid'])
            ->setConstructorArgs([$this->mockObjectManager])
            ->getMock();
        $this->extensionRepositoryMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository::class, [], [$this->mockObjectManager]);
        $this->repositoryHelperMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\Repository\Helper::class, ['updateExtList'], [], '', false);
        $this->languageServiceMock = $this->getMockBuilder(\TYPO3\CMS\Lang\LanguageService::class)
            ->setMethods(['__none'])
            ->getMock();
    }

    /**
     * @test
     * @return void
     */
    public function updateExtensionListFromTerCallsUpdateExtListIfExtensionListIsEmpty()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface|UpdateFromTerController $controllerMock */
        $controllerMock = $this->getAccessibleMock(UpdateFromTerController::class, ['getLanguageService']);
        $controllerMock->expects($this->any())->method('getLanguageService')->will($this->returnValue($this->languageServiceMock));

        $repositoryModelMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Repository::class, ['getLastUpdate']);
        $viewMock = $this->getAccessibleMock(\TYPO3\CMS\Fluid\View\TemplateView::class, ['assign'], [], '', false);
        $requestMock = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Request::class, ['hasArgument', 'getArgument']);
        $viewMock->expects($this->any())->method('assign')->will($this->returnValue($viewMock));
        $this->repositoryRepositoryMock->expects($this->once())->method('findByUid')->with(1)->will($this->returnValue($repositoryModelMock));
        $this->repositoryHelperMock->expects($this->once())->method('updateExtList');
        $this->extensionRepositoryMock->expects($this->once())->method('countAll')->will($this->returnValue(0));
        $controllerMock->_set('extensionRepository', $this->extensionRepositoryMock);
        $controllerMock->_set('repositoryRepository', $this->repositoryRepositoryMock);
        $controllerMock->_set('repositoryHelper', $this->repositoryHelperMock);
        $controllerMock->_set('settings', ['repositoryUid' => 1]);
        $controllerMock->_set('view', $viewMock);
        $controllerMock->_set('request', $requestMock);
        $controllerMock->updateExtensionListFromTerAction();
    }

    /**
     * @test
     * @return void
     */
    public function updateExtensionListFromTerDoesNotCallsUpdateExtListIfExtensionListIsNotEmpty()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface|UpdateFromTerController $controllerMock */
        $controllerMock = $this->getAccessibleMock(UpdateFromTerController::class, ['getLanguageService']);
        $controllerMock->expects($this->any())->method('getLanguageService')->will($this->returnValue($this->languageServiceMock));

        $repositoryModelMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Repository::class, ['getLastUpdate']);
        $viewMock = $this->getAccessibleMock(\TYPO3\CMS\Fluid\View\TemplateView::class, ['assign'], [], '', false);
        $requestMock = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Request::class, ['hasArgument', 'getArgument']);
        $viewMock->expects($this->any())->method('assign')->will($this->returnValue($viewMock));
        $this->repositoryRepositoryMock->expects($this->once())->method('findByUid')->with(1)->will($this->returnValue($repositoryModelMock));
        $this->repositoryHelperMock->expects($this->never())->method('updateExtList');
        $this->extensionRepositoryMock->expects($this->once())->method('countAll')->will($this->returnValue(100));
        $controllerMock->_set('extensionRepository', $this->extensionRepositoryMock);
        $controllerMock->_set('repositoryRepository', $this->repositoryRepositoryMock);
        $controllerMock->_set('repositoryHelper', $this->repositoryHelperMock);
        $controllerMock->_set('settings', ['repositoryUid' => 1]);
        $controllerMock->_set('view', $viewMock);
        $controllerMock->_set('request', $requestMock);
        $controllerMock->updateExtensionListFromTerAction();
    }

    /**
     * @test
     * @return void
     */
    public function updateExtensionListFromTerCallsUpdateExtListIfForceUpdateCheckIsSet()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface|UpdateFromTerController $controllerMock */
        $controllerMock = $this->getAccessibleMock(UpdateFromTerController::class, ['getLanguageService']);
        $controllerMock->expects($this->any())->method('getLanguageService')->will($this->returnValue($this->languageServiceMock));

        $repositoryModelMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Repository::class, ['getLastUpdate']);
        $viewMock = $this->getAccessibleMock(\TYPO3\CMS\Fluid\View\TemplateView::class, ['assign'], [], '', false);
        $requestMock = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Request::class, ['hasArgument', 'getArgument']);
        $viewMock->expects($this->any())->method('assign')->will($this->returnValue($viewMock));
        $this->repositoryRepositoryMock->expects($this->once())->method('findByUid')->with(1)->will($this->returnValue($repositoryModelMock));
        $this->repositoryHelperMock->expects($this->once())->method('updateExtList');
        $this->extensionRepositoryMock->expects($this->once())->method('countAll')->will($this->returnValue(100));
        $controllerMock->_set('extensionRepository', $this->extensionRepositoryMock);
        $controllerMock->_set('repositoryRepository', $this->repositoryRepositoryMock);
        $controllerMock->_set('repositoryHelper', $this->repositoryHelperMock);
        $controllerMock->_set('settings', ['repositoryUid' => 1]);
        $controllerMock->_set('view', $viewMock);
        $controllerMock->_set('request', $requestMock);
        $controllerMock->updateExtensionListFromTerAction(true);
    }
}
