<?php
namespace TYPO3\CMS\SysNote\Core;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Kai Vogel <kai.vogel@speedprogs.de>, Speedprogs.de
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * Bootstrap for note module
 *
 * @author Kai Vogel <kai.vogel@speedprogs.de>
 */
class Bootstrap {

	/**
	 * @var string
	 */
	protected $vendorName = 'TYPO3\CMS';

	/**
	 * @var string
	 */
	protected $extensionName = 'SysNote';

	/**
	 * @var string
	 */
	protected $pluginName = 'Note';

	/**
	 * @var string
	 */
	protected $controllerName = 'Note';

	/**
	 * Bootstrap extbase and execute note controller
	 *
	 * @param string $actionName Action to run
	 * @param array $arguments Optional arguments for the controller
	 * @return string
	 */
	public function run($actionName = 'index', array $arguments = array()) {
		// Yeah, this is ugly. But currently, there is no other direct way
		// in extbase to force a specific controller in backend mode.
		// Overwriting $_GET was the most simple solution here until extbase
		// provides a clean way to solve this.
		$_GET['tx_sysnote_note'] = $arguments;
		$_GET['tx_sysnote_note']['controller'] = $this->controllerName;
		$_GET['tx_sysnote_note']['action'] = $actionName;
		/** @var $extbaseBootstrap \TYPO3\CMS\Extbase\Core\Bootstrap */
		$extbaseBootstrap = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Core\\Bootstrap');
		return $extbaseBootstrap->run('', get_object_vars($this));
	}

}
?>