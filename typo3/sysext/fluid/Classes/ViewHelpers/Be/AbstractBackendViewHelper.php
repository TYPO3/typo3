<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * The abstract base class for all backend view helpers
 * Note: backend view helpers are still experimental!
 */
abstract class AbstractBackendViewHelper extends AbstractViewHelper {

	/**
	 * Gets instance of template if exists or create a new one.
	 * Saves instance in viewHelperVariableContainer
	 *
	 * @return DocumentTemplate $doc
	 */
	public function getDocInstance() {
		if ($this->viewHelperVariableContainer->exists(AbstractBackendViewHelper::class, 'DocumentTemplate')) {
			$doc = $this->viewHelperVariableContainer->get(AbstractBackendViewHelper::class, 'DocumentTemplate');
		} else {
			/** @var $doc DocumentTemplate */
			$doc = GeneralUtility::makeInstance(DocumentTemplate::class);
			$doc->backPath = $GLOBALS['BACK_PATH'];
			$this->viewHelperVariableContainer->add(AbstractBackendViewHelper::class, 'DocumentTemplate', $doc);
		}

		return $doc;
	}
}
