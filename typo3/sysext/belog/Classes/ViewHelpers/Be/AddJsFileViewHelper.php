<?php
namespace TYPO3\CMS\Belog\ViewHelpers\Be;

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
 * View helper to add a additional javascript file to the backend header
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class AddJsFileViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {

	/**
	 * Includes a JavaScript file into the to-be-rendered page
	 *
	 * @param string $file Custom JavaScript file to be loaded. Can be a file path inside the TYPO3 installation
	 * @return void
	 * @see \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @see \TYPO3\CMS\Core\Page\PageRenderer
	 */
	public function render($file) {
		$doc = $this->getDocInstance();
		$pageRenderer = $doc->getPageRenderer();
		$pageRenderer->addJsFile($file);
	}

}
