<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * The abstract base class for all backend view helpers
 * Note: backend view helpers are still experimental!
 */
abstract class AbstractBackendViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Gets instance of template if exists or create a new one.
	 * Saves instance in viewHelperVariableContainer
	 *
	 * @return \TYPO3\CMS\Backend\Template\DocumentTemplate $doc
	 */
	public function getDocInstance() {
		if (!isset($GLOBALS['SOBE']->doc)) {
			/** @var $doc \TYPO3\CMS\Backend\Template\DocumentTemplate */
			$doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
			$doc->backPath = $GLOBALS['BACK_PATH'];
			if (is_object($GLOBALS['SOBE'])) {
				$GLOBALS['SOBE']->doc = $doc;
			}
		} else {
			$doc = $GLOBALS['SOBE']->doc;
		}
		return $doc;
	}

}


?>