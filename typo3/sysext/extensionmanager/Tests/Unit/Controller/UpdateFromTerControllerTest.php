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

/**
 * Update from TER controller test
 *
 */
class UpdateFromTerControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
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

    protected function setUp()
    {
        $this->mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $this->repositoryRepositoryMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository::class, ['findByUid'], [$this->mockObjectManager]);
        $this->extensionRepositoryMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository::class, [], [$this->mockObjectManager]);
        $this->repositoryHelperMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\Repository\Helper::class, ['updateExtList'], [], '', false);
    }

    /**
     * @test
     * @return void
     */
    public function updateExtensionListFromTerCallsUpdateExtListIfExtensionListIsEmpty()
    {
        $controllerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Controller\UpdateFromTerController::class, ['dummy']);
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
        $controllerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Controller\UpdateFromTerController::class, ['dummy']);
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
        $controllerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Controller\UpdateFromTerController::class, ['dummy']);
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
