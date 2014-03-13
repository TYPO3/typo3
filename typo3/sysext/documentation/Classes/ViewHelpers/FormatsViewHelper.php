<?php
namespace TYPO3\CMS\Documentation\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Xavier Perseguers, <xavier@typo3.org>
 *  (c) 2013 Andrea Schmuttermair, <spam@schmutt.de>
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
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * ViewHelper to display all download links for a document
 *
 * Example: <doc:formats document="{document}" />
 */
class FormatsViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Renders all format download links.
	 *
	 * @param \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation $documentTranslation
	 * @return string
	 */
	public function render(\TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation $documentTranslation) {
		$output = '';
		foreach ($documentTranslation->getFormats() as $format) {
			/** @var \TYPO3\CMS\Documentation\Domain\Model\DocumentFormat $format */
			$output .= '<a ';

			$uri = '../' . $format->getPath();
			$extension = substr($uri, strrpos($uri, '.') + 1);
			if (strlen($extension) < 5) {
				// This is direct link to a file
				$output .= 'href="' . $uri . '"';
			} else {
				$extension = $format->getFormat();
				if ($extension === 'json') {
					$extension = 'js';
				}
				$output .= 'href="#" onclick="top.TYPO3.Backend.ContentContainer.setUrl(\'' . $uri . '\')"';
			}

			$xliff = 'LLL:EXT:documentation/Resources/Private/Language/locallang.xlf';
			$title = sprintf(
				$GLOBALS['LANG']->sL($xliff . ':tx_documentation_domain_model_documentformat.format.title'),
				$format->getFormat()
			);
			$output .= ' title="' . htmlspecialchars($title) . '">';
			$spriteIconHtml = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile($extension);
			$output .= $spriteIconHtml . '</a>' . LF;
		}
		return $output;
	}

}
