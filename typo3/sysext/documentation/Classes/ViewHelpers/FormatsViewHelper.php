<?php
namespace TYPO3\CMS\Documentation\ViewHelpers;

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
use TYPO3\CMS\Backend\Utility\IconUtility;

/**
 * ViewHelper to display all download links for a document
 *
 * Example: <doc:formats document="{document}" />
 *
 * @internal
 */
class FormatsViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Renders all format download links.
	 *
	 * @param \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation $documentTranslation
	 * @return string
	 */
	public function render(\TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation $documentTranslation) {
		$icons = array(
			'html' => '<a class="btn disabled">' . IconUtility::getSpriteIcon('empty-empty') . '</a>',
			'pdf' => '<a class="btn disabled">' . IconUtility::getSpriteIcon('empty-empty') . '</a>',
			'sxw' => '<a class="btn disabled">' . IconUtility::getSpriteIcon('empty-empty') . '</a>'
		);
		$formats = $documentTranslation->getFormats();

		foreach ($formats as $format) {
			$output = '';
			/** @var \TYPO3\CMS\Documentation\Domain\Model\DocumentFormat $format */
			$output .= '<a ';

			$uri = '../' . $format->getPath();
			$extension = substr($uri, strrpos($uri, '.') + 1);
			if (strlen($extension) < 5) {
				// This is direct link to a file
				$output .= 'href="' . $uri . '" class="btn"';
			} else {
				$extension = $format->getFormat();
				if ($extension === 'json') {
					$extension = 'js';
				}
				$output .= 'href="#" onclick="top.TYPO3.Backend.ContentContainer.setUrl(\'' . $uri . '\')" class="btn"';
			}

			$xliff = 'LLL:EXT:documentation/Resources/Private/Language/locallang.xlf';
			$title = sprintf(
				$GLOBALS['LANG']->sL($xliff . ':tx_documentation_domain_model_documentformat.format.title'),
				$format->getFormat()
			);
			$output .= ' title="' . htmlspecialchars($title) . '">';
			$spriteIconHtml = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile($extension);
			$output .= $spriteIconHtml . '</a>' . LF;
			$icons[$format->getFormat()] = $output;
		}
		return implode('', array_values($icons));
	}
}
