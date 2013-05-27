<?php
namespace TYPO3\CMS\Extbase\Core;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Dispatches a request
 */
class ModuleRunner implements ModuleRunnerInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * This method forwards the call to Bootstrap's run() method. This method is invoked by the mod.php
	 * function of TYPO3.
	 *
	 * @param string $moduleSignature
	 * @throws \RuntimeException
	 * @return boolean TRUE, if the request request could be dispatched
	 * @see run()
	 */
	public function callModule($moduleSignature) {
		if (!isset($GLOBALS['TBE_MODULES']['_configuration'][$moduleSignature])) {
			return FALSE;
		}
		$moduleConfiguration = $GLOBALS['TBE_MODULES']['_configuration'][$moduleSignature];

		// Check permissions and exit if the user has no permission for entry
		$GLOBALS['BE_USER']->modAccess($moduleConfiguration, TRUE);
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id')) {
			// Check page access
			$permClause = $GLOBALS['BE_USER']->getPagePermsClause(TRUE);
			$access = is_array(\TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess((integer) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'), $permClause));
			if (!$access) {
				throw new \RuntimeException('You don\'t have access to this page', 1289917924);
			}
		}

		// BACK_PATH is the path from the typo3/ directory from within the
		// directory containing the controller file. We are using mod.php dispatcher
		// and thus we are already within typo3/ because we call typo3/mod.php
		$GLOBALS['BACK_PATH'] = '';
		$configuration = array(
			'extensionName' => $moduleConfiguration['extensionName'],
			'pluginName' => $moduleSignature
		);
		if (isset($moduleConfiguration['vendorName'])) {
			$configuration['vendorName'] = $moduleConfiguration['vendorName'];
		}

		$bootstrap = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Core\\BootstrapInterface');
		$content = $bootstrap->run('', $configuration);
		print $content;

		return TRUE;
	}
}

?>