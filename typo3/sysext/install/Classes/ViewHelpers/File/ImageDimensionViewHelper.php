<?php
namespace TYPO3\CMS\Install\ViewHelpers\File;

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

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Get width or height from image file
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:file.imageDimension>/var/www/typo3/instance/typo3temp/foo.jpg</f:file.size>
 * </code>
 * <output>
 * 170
 * </output>
 *
 * @internal
 */
class ImageDimensionViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Get width / height from image file
     *
     * @param string $dimension Either width or height
     * @throws \TYPO3\CMS\Install\ViewHelpers\Exception
     * @return int width or height
     */
    public function render($dimension = 'width')
    {
        return static::renderStatic(
            [
                'dimension' => $dimension,
            ],
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
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $dimension = $arguments['dimension'];
        if ($dimension !== 'width' && $dimension !== 'height') {
            throw new \TYPO3\CMS\Install\ViewHelpers\Exception(
                'Dimension must be either \'width\' or \'height\'',
                1369563247
            );
        }
        $absolutePathToFile = $renderChildrenClosure();
        if (!is_file($absolutePathToFile)) {
            throw new \TYPO3\CMS\Install\ViewHelpers\Exception(
                'File not found',
                1369563248
            );
        }
        $actualDimension = getimagesize($absolutePathToFile);
        if ($dimension === 'width') {
            $size = $actualDimension[0];
        } else {
            $size = $actualDimension[1];
        }
        return $size;
    }
}
