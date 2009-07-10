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
 * @package TYPO3
 * @subpackage Extbase
 * @version $Id: QueryFactory.php 658 2009-05-16 13:54:16Z jocrau $
 */
class Tx_Extbase_Persistence_QueryFactory implements Tx_Extbase_Persistence_QueryFactoryInterface {

	/**
	 * Creates a query object working on the given class name
	 *
	 * @param string $className The class name
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function create($className) {
		$persistenceManager = t3lib_div::makeInstance('Tx_Extbase_Persistence_Manager'); // singleton; initialized in the dispatcher

		$dataMapper = t3lib_div::makeInstance('Tx_Extbase_Persistence_Mapper_DataMapper');
		$dataMapper->injectIdentityMap($persistenceManager->getBackend()->getIdentityMap());
		$dataMapper->injectPersistenceManager($persistenceManager);

		$query = t3lib_div::makeInstance('Tx_Extbase_Persistence_Query', $className);
		$query->injectPersistenceManager($persistenceManager);
		$query->injectDataMapper($dataMapper);
		
		return $query;
	}

}
?>