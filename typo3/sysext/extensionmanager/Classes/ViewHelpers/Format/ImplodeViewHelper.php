<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers\Format;

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
 * View Helper for imploding arrays
 * @internal
 */
class ImplodeViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Implodes a string
     *
     * @param array $implode
     * @param string $delimiter
     * @return string the altered string.
     * @api
     */
    public function render(array $implode, $delimiter = ', ')
    {
        return static::renderStatic(
            [
                'implode' => $implode,
                'delimiter' => $delimiter,
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
        return implode($arguments['delimiter'], $arguments['implode']);
    }
}
