<?php
namespace TYPO3\CMS\Scheduler\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Lorenz Ulrich <lorenz.ulrich@visol.ch>
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
 * This class provides Scheduler plugin implementation
 *
 * @author Lorenz Ulrich <lorenz.ulrich@visol.ch>
 * @deprecated since TYPO3 CMS 6.2 LTS - will be removed 2 versions later
 */
class FileIndexingTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

	/**
	 * @var string
	 */
	protected $indexingConfiguration;

	/**
	 * @var string
	 */
	protected $paths;

	/**
	 * Get the value of the protected property indexingConfiguration
	 *
	 * @return string UID of indexing configuration used for the job
	 */
	public function getIndexingConfiguration() {
		return $this->indexingConfiguration;
	}

	/**
	 * Set the value of the private property indexingConfiguration
	 *
	 * @param string $indexingConfiguration UID of indexing configuration used for the job
	 * @return void
	 */
	public function setIndexingConfiguration($indexingConfiguration) {
		$this->indexingConfiguration = $indexingConfiguration;
	}

	/**
	 * Get the value of the protected property paths
	 *
	 * @return string path information for scheduler job (JSON encoded array)
	 */
	public function getPaths() {
		return $this->paths;
	}

	/**
	 * Set the value of the private property paths
	 *
	 * @param array $paths path information for scheduler job (JSON encoded array)
	 * @return void
	 */
	public function setPaths($paths) {
		$this->paths = $paths;
	}

	/**
	 * Hardcode disabled state
	 *
	 * @return boolean TRUE if task is disabled, FALSE otherwise
	 */
	public function isDisabled() {
		return TRUE;
	}

	/**
	 * Function execute from the Scheduler
	 *
	 * @return boolean TRUE on successful execution, FALSE on error
	 */
	public function execute() {
		return FALSE;
	}

}
