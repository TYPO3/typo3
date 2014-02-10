<?php
namespace TYPO3\CMS\Frontend\Aspect;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Steffen Ritter <steffen.rittertypo3.org>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
 * Class FileMetadataTranslationAspect
 *
 * We do not have AOP in TYPO3 for now, thus the aspect which
 * deals with metadata translation is a slot which reacts on a signal
 * in the Index\MetadataRepository.
 *
 * The aspect injects user permissions and mount points into the storage
 * based on user or group configuration.
 */
class FileMetadataOverlayAspect {

	/**
	 * Do translation and workspace overlay
	 *
	 * @param \ArrayObject $data
	 * @return void
	 */
	public function languageAndWorkspaceOverlay(\ArrayObject $data) {
		$overlayedMetaData = $this->getTsfe()->sys_page->getRecordOverlay(
			'sys_file_metadata',
			$data->getArrayCopy(),
			$this->getTsfe()->sys_language_content,
			$this->getTsfe()->sys_language_contentOL
		);
		if ($overlayedMetaData !== NULL) {
			$data->exchangeArray($overlayedMetaData);
		}
	}

	/**
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected function getTsfe() {
		return $GLOBALS['TSFE'];
	}
}
