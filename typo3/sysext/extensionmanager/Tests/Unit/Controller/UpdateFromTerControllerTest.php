<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Controller;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012 Susanne Moog, <typo3@susannemoog.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Update from TER controller test
 *
 * @package Extension Manager
 * @subpackage Tests
 */
class UpdateFromTerControllerTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * @test
	 * @return void
	 */
	public function updateExtensionListFromTerCallsUpdateExtListIfLastUpdateIsMoreThan24HoursAgo() {
		$controllerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Controller\\UpdateFromTerController', array('dummy'));
		$repositoryRepositoryMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\RepositoryRepository', array('findOneByUid'));
		$repositoryModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Repository', array('getLastUpdate'));
		$repositoryHelperMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\Repository\\Helper', array('updateExtList'));
		$viewMock = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\View\\TemplateView', array('assign'));
		$requestMock = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Mvc\\Request', array('hasArgument', 'getArgument'));
		$viewMock->expects($this->any())->method('assign')->will($this->returnValue($viewMock));
		$lastUpdateDate = new \DateTime();
		// Wed Jul 25 18:40:02 CEST 2012
		$lastUpdateDate->setTimestamp(1343234402);
		$repositoryModelMock->expects($this->once())->method('getLastUpdate')->will($this->returnValue($lastUpdateDate));
		$repositoryRepositoryMock->expects($this->once())->method('findOneByUid')->with(1)->will($this->returnValue($repositoryModelMock));
		$repositoryHelperMock->expects($this->once())->method('updateExtList');
		// Sat Jul 28 18:40:02 CEST 2012
		$GLOBALS['EXEC_TIME'] = 1343493602;
		$controllerMock->_set('repositoryRepository', $repositoryRepositoryMock);
		$controllerMock->_set('repositoryHelper', $repositoryHelperMock);
		$controllerMock->_set('settings', array('repositoryUid' => 1));
		$controllerMock->_set('view', $viewMock);
		$controllerMock->_set('request', $requestMock);
		$controllerMock->updateExtensionListFromTerAction();
	}

	/**
	 * @test
	 * @return void
	 */
	public function updateExtensionListFromTerDoesNotCallUpdateExtListIfLastUpdateIsLessThan24HoursAgo() {
		$controllerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Controller\\UpdateFromTerController', array('dummy'));
		$repositoryRepositoryMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\RepositoryRepository', array('findOneByUid'));
		$repositoryModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Repository', array('getLastUpdate'));
		$repositoryHelperMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\Repository\\Helper', array('updateExtList'));
		$viewMock = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\View\\TemplateView', array('assign'));
		$requestMock = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Mvc\\Request', array('hasArgument', 'getArgument'));
		$viewMock->expects($this->any())->method('assign')->will($this->returnValue($viewMock));
		$lastUpdateDate = new \DateTime();
		// Wed Jul 25 18:40:02 CEST 2012
		$lastUpdateDate->setTimestamp(1343493602);
		$repositoryModelMock->expects($this->once())->method('getLastUpdate')->will($this->returnValue($lastUpdateDate));
		$repositoryRepositoryMock->expects($this->once())->method('findOneByUid')->with(1)->will($this->returnValue($repositoryModelMock));
		$repositoryHelperMock->expects($this->never())->method('updateExtList');
		// Sat Jul 28 18:40:02 CEST 2012
		$GLOBALS['EXEC_TIME'] = 1343493602;
		$controllerMock->_set('repositoryRepository', $repositoryRepositoryMock);
		$controllerMock->_set('repositoryHelper', $repositoryHelperMock);
		$controllerMock->_set('settings', array('repositoryUid' => 1));
		$controllerMock->_set('view', $viewMock);
		$controllerMock->_set('request', $requestMock);
		$controllerMock->updateExtensionListFromTerAction();
	}

	/**
	 * @test
	 * @return void
	 */
	public function updateExtensionListFromTerCallsUpdateExtListIfForceUpdateCheckIsSet() {
		$controllerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Controller\\UpdateFromTerController', array('dummy'));
		$repositoryRepositoryMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\RepositoryRepository', array('findOneByUid'));
		$repositoryModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Repository', array('getLastUpdate'));
		$repositoryHelperMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\Repository\\Helper', array('updateExtList'));
		$viewMock = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\View\\TemplateView', array('assign'));
		$viewMock->expects($this->any())->method('assign')->will($this->returnValue($viewMock));
		$lastUpdateDate = new \DateTime();
		// Wed Jul 25 18:40:02 CEST 2012
		$lastUpdateDate->setTimestamp(1343234402);
		$repositoryModelMock->expects($this->once())->method('getLastUpdate')->will($this->returnValue($lastUpdateDate));
		$repositoryRepositoryMock->expects($this->once())->method('findOneByUid')->with(1)->will($this->returnValue($repositoryModelMock));
		$repositoryHelperMock->expects($this->once())->method('updateExtList');
		// Sat Jul 28 18:40:02 CEST 2012
		$GLOBALS['EXEC_TIME'] = 1343493602;
		$controllerMock->_set('repositoryRepository', $repositoryRepositoryMock);
		$controllerMock->_set('repositoryHelper', $repositoryHelperMock);
		$controllerMock->_set('settings', array('repositoryUid' => 1));
		$controllerMock->_set('view', $viewMock);
		$controllerMock->_set('request', $requestMock);
		$controllerMock->updateExtensionListFromTerAction(TRUE);
	}

}


?>