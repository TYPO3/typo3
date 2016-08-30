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
 * Get file path relative to PATH_site from absolute path
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:file.relativePath>/var/www/typo3/instance/typo3temp/foo.jpg</f:file.relativePath>
 * </code>
 * <output>
 * typo3temp/foo.jpg
 * </output>
 *
 * @internal
 */
class RelativePathViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Get relative path
     *
     * @return string Relative path
     */
    public function render()
    {
        return static::renderStatic(
            [],
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
        $absolutePath = $renderChildrenClosure();
        return \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($absolutePath);
    }
}
