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
 * View helper to add a additional javascript settings to the backend header
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class InlineSettingsArrayViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {

	/**
	 * Render additional javascript to page
	 *
	 * @param array $settings Custom JavaScript settings to be added
	 * @param string $namespace Set custom namespace for inline settings array
	 * @return void
	 * @see \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @see \TYPO3\CMS\Core\Page\PageRenderer
	 */
	public function render(array $settings, $namespace = '') {
		$doc = $this->getDocInstance();
		$pageRenderer = $doc->getPageRenderer();
		$pageRenderer->addInlineSettingArray($namespace, $settings);
	}

}
