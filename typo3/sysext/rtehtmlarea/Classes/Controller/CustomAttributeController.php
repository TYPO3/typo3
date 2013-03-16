<?php
namespace TYPO3\CMS\Rtehtmlarea\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2013 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Render custom attribute data-htmlarea-clickenlarge
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class CustomAttributeController extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {

	// Default plugin variables:
	/**
	 * @todo Define visibility
	 */
	public $prefixId = 'tx_rtehtmlarea_pi3';

	// Same as class name
	/**
	 * @todo Define visibility
	 */
	public $scriptRelPath = 'pi3/class.tx_rtehtmlarea_pi3.php';

	// Path to this script relative to the extension dir.
	/**
	 * @todo Define visibility
	 */
	public $extKey = 'rtehtmlarea';

	// The extension key.
	/**
	 * @todo Define visibility
	 */
	public $conf = array();

	/**
	 * cObj object
	 *
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 * @todo Define visibility
	 */
	public $cObj;

	/**
	 * Rendering the "data-htmlarea-clickenlarge" custom attribute, called from TypoScript
	 *
	 * @param 	string		Content input. Not used, ignore.
	 * @param 	array		TypoScript configuration
	 * @return 	string		HTML output.
	 * @access private
	 * @todo Define visibility
	 */
	public function render_clickenlarge($content, $conf) {
		$clickenlarge = isset($this->cObj->parameters['data-htmlarea-clickenlarge']) ? $this->cObj->parameters['data-htmlarea-clickenlarge'] : 0;
		if (!$clickenlarge) {
			// Backward compatibility
			$clickenlarge = isset($this->cObj->parameters['clickenlarge']) ? $this->cObj->parameters['clickenlarge'] : 0;
		}
		$fileFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
		$fileTable = $this->cObj->parameters['data-htmlarea-file-table'];
		$fileUid = $this->cObj->parameters['data-htmlarea-file-uid'];
		if ($fileUid) {
			$fileObject = $fileFactory->getFileObject($fileUid);
			$filePath = $fileObject->getForLocalProcessing(FALSE);
			$file = substr($filePath, strlen(PATH_site));
		} else {
			// Pre-FAL backward compatibility
			$path = $this->cObj->parameters['src'];
			$magicFolder = $fileFactory->getFolderObjectFromCombinedIdentifier($GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir']);
			if ($magicFolder instanceof \TYPO3\CMS\Core\Resource\Folder) {
				$magicFolderPath = $magicFolder->getPublicUrl();
				$pathPre = $magicFolderPath . 'RTEmagicC_';
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($path, $pathPre)) {
					// Find original file:
					$pI = pathinfo(substr($path, strlen($pathPre)));
					$filename = substr($pI['basename'], 0, -strlen(('.' . $pI['extension'])));
					$file = $magicFolderPath . 'RTEmagicP_' . $filename;
				} else {
					$file = $this->cObj->parameters['src'];
				}
			}
		}
		// Unset clickenlarge custom attribute
		unset($this->cObj->parameters['data-htmlarea-clickenlarge']);
		// Backward compatibility
		unset($this->cObj->parameters['clickenlarge']);
		unset($this->cObj->parameters['allParams']);
		$content = '<img ' . \TYPO3\CMS\Core\Utility\GeneralUtility::implodeAttributes($this->cObj->parameters, TRUE, TRUE) . ' />';
		if ($clickenlarge && is_array($conf['imageLinkWrap.'])) {
			$theImage = $file ? $GLOBALS['TSFE']->tmpl->getFileName($file) : '';
			if ($theImage) {
				$this->cObj->parameters['origFile'] = $theImage;
				if ($this->cObj->parameters['title']) {
					$conf['imageLinkWrap.']['title'] = $this->cObj->parameters['title'];
				}
				if ($this->cObj->parameters['alt']) {
					$conf['imageLinkWrap.']['alt'] = $this->cObj->parameters['alt'];
				}
				$content = $this->cObj->imageLinkWrap($content, $theImage, $conf['imageLinkWrap.']);
				$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
			}
		}
		return $content;
	}

}


?>