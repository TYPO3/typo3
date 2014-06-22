<?php
namespace TYPO3\CMS\Extbase\Core;

/**
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
			$access = is_array(\TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess((int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'), $permClause));
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
