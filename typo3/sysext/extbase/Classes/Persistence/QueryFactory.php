<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * The QueryFactory used to create queries against the storage backend
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: QueryFactory.php 2264 2010-05-02 09:37:43Z jocrau $
 */
class Tx_Extbase_Persistence_QueryFactory implements Tx_Extbase_Persistence_QueryFactoryInterface, t3lib_Singleton {

	/**
	 * Creates a query object working on the given class name
	 *
	 * @param string $className The class name
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function create($className) {
		$persistenceManager = Tx_Extbase_Dispatcher::getPersistenceManager();

		$reflectionService = $persistenceManager->getBackend()->getReflectionService();
		
		$dataMapFactory = t3lib_div::makeInstance('Tx_Extbase_Persistence_Mapper_DataMapFactory');
		$dataMapFactory->injectReflectionService($reflectionService);

		$dataMapper = t3lib_div::makeInstance('Tx_Extbase_Persistence_Mapper_DataMapper');
		$dataMapper->injectIdentityMap($persistenceManager->getBackend()->getIdentityMap());
		$dataMapper->injectSession($persistenceManager->getSession());
		$dataMapper->injectReflectionService($reflectionService);
		$dataMapper->injectDataMapFactory($dataMapFactory);
		
		$querySettings = t3lib_div::makeInstance('Tx_Extbase_Persistence_Typo3QuerySettings');

		$query = t3lib_div::makeInstance('Tx_Extbase_Persistence_Query', $className);
		$query->injectPersistenceManager($persistenceManager);
		$query->injectDataMapper($dataMapper);
		$query->setQuerySettings($querySettings);

		return $query;
	}
}
?>