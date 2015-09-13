<?php
namespace TYPO3\CMS\Documentation\ViewHelpers;

/*
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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * ViewHelper to display all download links for a document
 *
 * Example: <doc:formats document="{document}" />
 *
 * @internal
 */
class FormatsViewHelper extends AbstractViewHelper implements CompilableInterface {

	/**
	 * Renders all format download links.
	 *
	 * @param \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation $documentTranslation
	 * @return string
	 */
	public function render(\TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation $documentTranslation) {
		return static::renderStatic(
			array(
				'documentTranslation' => $documentTranslation,
			),
			$this->buildRenderChildrenClosure(),
			$this->renderingContext
		);
	}

	/**
	 * @param array $arguments
	 * @param callable $renderChildrenClosure
	 * @param RenderingContextInterface $renderingContext
	 *
	 * @return string
	 */
	static public function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext) {
		/** @var \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation $documentTranslation */
		$documentTranslation = $arguments['documentTranslation'];

		$iconFactory = GeneralUtility::makeInstance(IconFactory::class);
		$emptyIcon = $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render();
		$icons = array(
			'html' => '<a class="btn btn-default disabled">' . $emptyIcon . '</a>',
			'pdf' => '<a class="btn btn-default disabled">' . $emptyIcon . '</a>',
			'sxw' => '<a class="btn btn-default disabled">' . $emptyIcon . '</a>'
		);
		$formats = $documentTranslation->getFormats();
		$iconFactory = GeneralUtility::makeInstance(IconFactory::class);

		foreach ($formats as $format) {
			$output = '';
			/** @var \TYPO3\CMS\Documentation\Domain\Model\DocumentFormat $format */
			$output .= '<a ';

			$uri = '../' . $format->getPath();
			$extension = substr($uri, strrpos($uri, '.') + 1);
			if (strlen($extension) < 5) {
				// This is direct link to a file
				$output .= 'href="' . $uri . '" class="btn btn-default"';
			} else {
				$extension = $format->getFormat();
				if ($extension === 'json') {
					$extension = 'js';
				}
				$output .= 'href="#" onclick="top.TYPO3.Backend.ContentContainer.setUrl(' . GeneralUtility::quoteJSvalue($uri) . ')" class="btn btn-default"';
			}

			$xliff = 'LLL:EXT:documentation/Resources/Private/Language/locallang.xlf';
			$title = sprintf(
				$GLOBALS['LANG']->sL($xliff . ':tx_documentation_domain_model_documentformat.format.title'),
				$format->getFormat()
			);
			$output .= ' title="' . htmlspecialchars($title) . '">';
			$spriteIconHtml = $iconFactory->getIconForFileExtension($extension, Icon::SIZE_SMALL)->render();
			$output .= $spriteIconHtml . '</a>' . LF;
			$keyFormat = $format->getFormat();
			if ($keyFormat === 'json') {
				// It should take over the place of sxw which will then never be used
				$keyFormat = 'sxw';
			}
			$icons[$keyFormat] = $output;
		}
		return implode('', array_values($icons));
	}

}
