<?php
namespace TYPO3\CMS\Backend\Template;

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
 * Extension class for "template" - used for backend pages which were medium wide. Typically submodules to Web or File which were presented in the list-frame when the content frame were divided into a navigation and list frame.
 * The class were more significant in the past than today. But probably you should use this one for most modules you make.
 *
 * @deprecated since 6.1 will be removed two versions later
 */
class MediumDocumentTemplate extends \TYPO3\CMS\Backend\Template\DocumentTemplate {

	public function __construct() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		parent::__construct();
	}

}
