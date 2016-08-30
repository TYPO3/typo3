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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Get file size from file
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:file.size>/var/www/typo3/instance/typo3temp/foo.jpg</f:file.size>
 * </code>
 * <output>
 * 1,2k
 * </output>
 *
 * @internal
 */
class SizeViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Get size from file
     *
     * @param bool $format If true, file size will be formatted
     * @throws \TYPO3\CMS\Install\ViewHelpers\Exception
     * @return int File size
     */
    public function render($format = true)
    {
        return static::renderStatic(
            [
                'format' => $format,
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
        $format = $arguments['format'];

        $absolutePathToFile = $renderChildrenClosure();
        if (!is_file($absolutePathToFile)) {
            throw new \TYPO3\CMS\Install\ViewHelpers\Exception(
                'File not found',
                1369563246
            );
        }
        $size = filesize($absolutePathToFile);
        if ($format) {
            $size = GeneralUtility::formatSize($size);
        }
        return $size;
    }
}
