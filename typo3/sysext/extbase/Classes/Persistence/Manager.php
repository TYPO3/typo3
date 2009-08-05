<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * The Extbase Persistence Manager
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: Manager.php 2293 2009-05-20 18:14:45Z robert $
 *
 */
class Tx_Extbase_Persistence_Manager implements Tx_Extbase_Persistence_ManagerInterface, t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Persistence_BackendInterface
	 */
	protected $backend;

	/**
	 * @var Tx_Extbase_Persistence_Session
	 */
	protected $session;

	/**
	 * Injects the Persistence Backend
	 *
	 * @param Tx_Extbase_Persistence_BackendInterface $backend The persistence backend
	 * @return void

	 */
	public function injectBackend(Tx_Extbase_Persistence_BackendInterface $backend) {
		$this->backend = $backend;
	}

	/**
	 *
	 * Injects the Persistence Session
	 *
	 * @param Tx_Extbase_Persistence_Session $session The persistence session
	 * @return void

	 */
	public function injectSession(Tx_Extbase_Persistence_Session $session) {
		$this->session = $session;
	}

	/**
	 * Returns the current persistence session
	 *
	 * @return Tx_Extbase_Persistence_Session

	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * Returns the persistence backend
	 *
	 * @return Tx_Extbase_Persistence_BackendInterface
	 */
	public function getBackend() {
		return $this->backend;
	}

	/**
	 * Commits new objects and changes to objects in the current persistence
	 * session into the backend
	 *
	 * @return void
	 * @api
	 */
	public function persistAll() {
		$aggregateRootObjects = new Tx_Extbase_Persistence_ObjectStorage();
		$aggregateRootObjects->addAll($this->session->getAddedObjects());
		$aggregateRootObjects->addAll($this->session->getReconstitutedObjects());

		$removedObjects = $this->session->getRemovedObjects();

		$this->backend->setAggregateRootObjects($aggregateRootObjects);
		$this->backend->setDeletedObjects($removedObjects);
		$this->backend->commit();
	}
}
?>