<?php
namespace TYPO3\CMS\Cshmanual\ViewHelpers;

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
 * Format the given content
 *
 * @internal
 */
class FormatViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Disable the escaping interceptor
     *
     * @var bool
     */
    protected $escapingInterceptorEnabled = false;

    /**
     * Format the content
     *
     * @param string $content
     * @return string
     */
    public function render($content = '')
    {
        return self::renderStatic(
            [
                'content' => $content,
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        if (empty($content)) {
            $content = $renderChildrenClosure();
        }
        return nl2br(trim(strip_tags($content, '<strong><em><b><i>')));
    }
}
