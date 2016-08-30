<?php
namespace TYPO3\CMS\IndexedSearch\ViewHelpers\Format;

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
 * Group list viewhelper
 */
class GroupListViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Render the given group information as string
     *
     * @param array $groups
     * @return string
     */
    public function render(array $groups = [])
    {
        return static::renderStatic(
            [
                'groups' => $groups,
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
        $groups = $arguments['groups'];

        $str = [];
        foreach ($groups as $row) {
            $str[] = $row['gr_list'] === '0,-1' ? 'NL' : $row['gr_list'];
        }
        arsort($str);
        return htmlspecialchars(implode('|', $str));
    }
}
