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
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * ViewHelper to display all download links for a document
 *
 * Example: <doc:formats document="{document}" />
 *
 * @internal
 */
class FormatsViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Renders all format download links.
     *
     * @param \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation $documentTranslation
     * @return string
     */
    public function render(\TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation $documentTranslation)
    {
        return static::renderStatic(
            [
                'documentTranslation' => $documentTranslation,
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * Statically renders all format download links.
     *
     * @param array $arguments
     * @param callable $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        /** @var \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation $documentTranslation */
        $documentTranslation = $arguments['documentTranslation'];

        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $emptyIcon = $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render();
        $icons = [
            'html' => '<a class="btn btn-default disabled">' . $emptyIcon . '</a>',
            'pdf' => '<a class="btn btn-default disabled">' . $emptyIcon . '</a>',
            'sxw' => '<a class="btn btn-default disabled">' . $emptyIcon . '</a>'
        ];
        $formats = $documentTranslation->getFormats();

        foreach ($formats as $format) {
            $output = '';
            /** @var \TYPO3\CMS\Documentation\Domain\Model\DocumentFormat $format */
            $output .= '<a ';

            $uri = '../' . $format->getPath();
            $documentFormat = $format->getFormat();
            $extension = substr($uri, strrpos($uri, '.') + 1);
            if (strlen($extension) < 5) {
                // This is direct link to a file
                $output .= 'href="' . $uri . '" class="btn btn-default"';
                $iconHtml = static::getIconForFileExtension($extension, $iconFactory);
            } else {
                $output .= 'href="#" onclick="top.TYPO3.Backend.ContentContainer.setUrl(' . GeneralUtility::quoteJSvalue($uri) . ')" class="btn btn-default"';
                $iconHtml = static::getIconForFileExtension($documentFormat, $iconFactory);
            }

            $xliff = 'LLL:EXT:documentation/Resources/Private/Language/locallang.xlf';
            $title = sprintf(
                $GLOBALS['LANG']->sL($xliff . ':tx_documentation_domain_model_documentformat.format.title'),
                $documentFormat
            );
            $output .= ' title="' . htmlspecialchars($title) . '">';
            $output .= $iconHtml . '</a>' . LF;
            if ($documentFormat === 'json') {
                // It should take over the place of sxw which will then never be used
                $documentFormat = 'sxw';
            }
            $icons[$documentFormat] = $output;
        }
        return implode('', array_values($icons));
    }

    /**
     * Returns the icon associated to a given file extension (privileging black and white).
     *
     * @param IconFactory $iconFactory
     * @param string $extension
     * @return string
     */
    protected static function getIconForFileExtension($extension, IconFactory $iconFactory)
    {
        switch ($extension) {
            case 'html':
            case 'pdf':
                $iconHtml = $iconFactory->getIcon('actions-file-' . $extension, Icon::SIZE_SMALL)->render();
                break;
            case 'sxw':
                $iconHtml = $iconFactory->getIcon('actions-file-openoffice', Icon::SIZE_SMALL)->render();
                break;
            case 'json':
                $iconHtml = $iconFactory->getIcon('actions-system-extension-documentation', Icon::SIZE_SMALL)->render();
                break;
            default:
                $iconHtml = $iconFactory->getIconForFileExtension($extension, Icon::SIZE_SMALL)->render();
        }
        return $iconHtml;
    }
}
