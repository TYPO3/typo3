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

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

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
class ImageDimensionViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Output is escaped already. We must not escape children, to avoid double encoding.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('dimension', 'string', '', false, 'width');
    }

    /**
     * Get width / height from image file
     *
     * @throws \TYPO3\CMS\Install\ViewHelpers\Exception
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @throws \TYPO3\CMS\Install\ViewHelpers\Exception
     *
     * @return int width or height
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
