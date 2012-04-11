<?php
/*                                                                        *
 * This script belongs to the Extbase framework.                          *
 *                                                                        *
 * This class is a backport of the corresponding class of FLOW3.          *
 * All credits go to the v5 team.                                         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The Extbase Persistence Manager interface
 *
 * @package Extbase
 * @subpackage Persistence
 * @deprecated since Extbase 6.0, will be removed in Extbase 7.0
 */
interface Tx_Extbase_Persistence_ManagerInterface extends Tx_Extbase_Persistence_PersistenceManagerInterface {

	/**
	 * Returns the current persistence session
	 *
	 * @return Tx_Extbase_Persistence_Session
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 7.0
	 */
	public function getSession();

	/**
	 * Returns the persistence backend
	 *
	 * @return Tx_Extbase_Persistence_BackendInterface
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 7.0
	 */
	public function getBackend();
}
?>