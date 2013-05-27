<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Sebastian Kurfürst <sebastian@typo3.org>
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
 * A checker which hooks into the backend module "Reports" checking whether there
 * is a PHP accelerator in place which strips off Doc Comments.
 *
 * @package Extbase
 * @subpackage Utility
 * @version $Id$
 */
class Tx_Extbase_Utility_ExtbaseRequirementsCheck implements tx_reports_StatusProvider {

	/**
	 * Compiles a collection of system status checks as a status report.
	 *
	 * @see typo3/sysext/reports/interfaces/tx_reports_StatusProvider::getStatus()
	 */
	public function getStatus() {
		$reports = array(
			'docCommentsShouldBePreserved' => $this->checkIfDocCommentsArePreserved(),
			'dbalExtensionIsInstalled' => $this->checkIfDbalExtensionIsInstalled()
		);

		return $reports;
	}

	/**
	 * Check whether doc comments are preserved or stipped off PHP by PHP accelerators.
	 *
	 * @return tx_reports_reports_status_Status
	 */
	protected function checkIfDocCommentsArePreserved() {

		$method = new ReflectionMethod('Tx_Extbase_Core_Bootstrap', 'run');

		if(strlen($method->getDocComment()) > 0) {
			$value = 'Preserved';
			$message = '';
			$status = tx_reports_reports_status_Status::OK;
		} else {
			$value = 'Stripped';
			$message = 'The PHP Doc comments are stripped from the PHP files. All extensions based on Extbase will not work correctly.<br />Are you using a PHP Accelerator like eAccelerator? If you use eAccelerator, please recompile it with the compile flag <b>--with-eaccelerator-doc-comment-inclusion</b>. See <a href="http://eaccelerator.net/ticket/229">the eAccelerator bugtracker</a> for more details.';
			$status = tx_reports_reports_status_Status::ERROR;
		}
		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'PHP Doc Comments',
			$value,
			$message,
			$status
		);
	}

	/**
	 * Check whether dbal extension is installed
	 *
	 * @return tx_reports_reports_status_Status
	 */
	protected function checkIfDbalExtensionIsInstalled() {
		if (t3lib_extMgm::isLoaded('dbal')) {
			$value = 'DBAL is loaded';
			$message = 'The Database Abstraction Layer Extension (dbal) is loaded. Extbase does not fully support dbal at the moment. If you are aware of this fact or don\'t make use of the incompatible parts on this installation, you can ignore this notice.';
			$status = tx_reports_reports_status_Status::INFO;
		} else {
			$value = 'DBAL is not loaded';
			$message = '';
			$status = tx_reports_reports_status_Status::OK;
		}
		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'DBAL Extension',
			$value,
			$message,
			$status
		);
	}
}
?>