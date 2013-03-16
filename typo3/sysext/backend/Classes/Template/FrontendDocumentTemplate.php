<?php
namespace TYPO3\CMS\Backend\Template;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Extension class for "template" - used in the context of frontend editing.
 */
class FrontendDocumentTemplate extends \TYPO3\CMS\Backend\Template\DocumentTemplate {

	/**
	 * Gets instance of PageRenderer
	 *
	 * @return \TYPO3\CMS\Core\Page\PageRenderer
	 */
	public function getPageRenderer() {
		if (!isset($this->pageRenderer)) {
			$this->pageRenderer = $GLOBALS['TSFE']->getPageRenderer();
		}
		return $this->pageRenderer;
	}

	/**
	 * Used in the frontend context to insert header data via TSFE->additionalHeaderData.
	 * Mimics header inclusion from template->startPage().
	 *
	 * @return void
	 */
	public function insertHeaderData() {
		$this->backPath = ($GLOBALS['TSFE']->backPath = TYPO3_mainDir);
		$this->pageRenderer->setBackPath($this->backPath);
		$this->docStyle();
		// Add applied JS/CSS to $GLOBALS['TSFE']
		if ($this->JScode) {
			$this->pageRenderer->addHeaderData($this->JScode);
		}
		if (count($this->JScodeArray)) {
			foreach ($this->JScodeArray as $name => $code) {
				$this->pageRenderer->addJsInlineCode($name, $code, FALSE);
			}
		}
	}

}


?>